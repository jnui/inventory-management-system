<?php
require_once 'vendor/autoload.php';
require_once 'db_connection.php';

class NaturalLanguageProcessor {
    private $pdo;
    private $openai;
    private $itemNames;
    private $systemMessage;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;

        // Load OpenAI API key from env or fallback config.php
        $apiKey = getenv('OPENAI_API_KEY');
        if (!$apiKey) {
            $configPath = __DIR__ . '/config.php';
            if (file_exists($configPath)) {
                $cfg = include $configPath;
                if (is_array($cfg) && !empty($cfg['openai_api_key'])) {
                    $apiKey = $cfg['openai_api_key'];
                }
            }
        }

        if (!$apiKey) {
            throw new Exception('OPENAI_API_KEY is not configured. Set environment variable or add openai_api_key in config.php');
        }

        $this->openai = OpenAI::client($apiKey);
        
        // Initialize item names
        $stmt = $this->pdo->query("SELECT item_name FROM consumable_materials");
        $this->itemNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Set up system message
        $this->systemMessage = "You are a helpful inventory management assistant that parses natural language commands into structured data. " .
            "Available items: " . implode(", ", $this->itemNames) . "\n\n" .
            "Parse the command and return a JSON object with a 'commands' array. Each command in the array should have:\n" .
            "- action: 'add' or 'remove' (use 'remove' for 'taking', 'took', 'needs', 'wants'; use 'add' for 'returned', 'returning')\n" .
            "- item_name: EXACT match from the items list above\n" .
            "- quantity: number\n" .
            "- employee_name: first name of employee if mentioned\n\n" .
            "Example format:\n" .
            "{\n" .
            "  \"commands\": [\n" .
            "    {\"action\": \"remove\", \"item_name\": \"12 x 12 Tee\", \"quantity\": 1, \"employee_name\": \"Phil\"},\n" .
            "    {\"action\": \"add\", \"item_name\": \"12 inch, 90\", \"quantity\": 2, \"employee_name\": \"Phil\"}\n" .
            "  ]\n" .
            "}\n\n" .
            "IMPORTANT: The item_name MUST be an EXACT match from the items list above, including spaces and special characters. Do not try to guess or modify item names.";
    }
    
    private function normalizeText($text) {
        // Convert to lowercase and remove special characters
        $text = strtolower($text);
        
        // Replace double quotes with "inch" before removing special characters
        $text = str_replace('"', ' inch ', $text);
        
        // Remove special characters
        $text = preg_replace('/[^a-z0-9\s]/', '', $text);
        
        // Clean up any extra spaces
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }

    private function findSimilarItems($searchTerm) {
        $similarItems = [];
        $searchTerm = $this->normalizeText($searchTerm);
        $searchWords = explode(' ', $searchTerm);
        
        foreach ($this->itemNames as $itemName) {
            $normalizedItemName = $this->normalizeText($itemName);
            $itemWords = explode(' ', $normalizedItemName);
            
            // Count matching words
            $matchCount = 0;
            $allWordsMatch = true;
            
            // Check if all words from the search term are present in the item name
            foreach ($searchWords as $word) {
                $wordFound = false;
                foreach ($itemWords as $itemWord) {
                    // Check for partial matches
                    if (strpos($itemWord, $word) !== false || strpos($word, $itemWord) !== false) {
                        $wordFound = true;
                        $matchCount++;
                        break;
                    }
                }
                if (!$wordFound) {
                    $allWordsMatch = false;
                }
            }
            
            // If all words match, give it a higher priority
            if ($allWordsMatch) {
                $matchCount += 10;
            }
            
            // If at least one word matches, add to suggestions
            if ($matchCount > 0) {
                $similarItems[] = [
                    'name' => $itemName,
                    'matches' => $matchCount
                ];
            }
        }
        
        // Sort by number of matches (descending)
        usort($similarItems, function($a, $b) {
            return $b['matches'] - $a['matches'];
        });
        
        // Return just the names
        return array_column($similarItems, 'name');
    }
    
    private function parseCommands($content) {
        $parsed = json_decode($content, true);
        
        if (!$parsed || !isset($parsed['commands']) || !is_array($parsed['commands'])) {
            throw new Exception("Failed to parse command into valid format");
        }
        
        $results = [];
        foreach ($parsed['commands'] as $command) {
            if (!isset($command['action']) || !isset($command['item_name']) || !isset($command['quantity'])) {
                throw new Exception("Invalid command format");
            }
            
            // Look up employee ID if name is provided
            if (isset($command['employee_name'])) {
                $stmt = $this->pdo->prepare("SELECT id FROM employees WHERE first_name = ?");
                $stmt->execute([$command['employee_name']]);
                $employee = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$employee) {
                    throw new Exception("Employee not found: " . $command['employee_name']);
                }
                
                $command['employee_id'] = $employee['id'];
            }
            
            $results[] = $command;
        }
        
        return $results;
    }
    
    public function processCommand($command) {
        // First, check if the command contains any item names
        $normalizedCommand = $this->normalizeText($command);
        $hasExactMatch = false;
        $exactMatchItem = null;
        
        // Try to find an exact match, ignoring word order
        foreach ($this->itemNames as $itemName) {
            $normalizedItemName = $this->normalizeText($itemName);
            $itemWords = explode(' ', $normalizedItemName);
            $commandWords = explode(' ', $normalizedCommand);
            
            // Check if all words from the item name are present in the command
            $allWordsMatch = true;
            foreach ($itemWords as $itemWord) {
                $wordFound = false;
                foreach ($commandWords as $commandWord) {
                    // Check for partial matches
                    if (strpos($commandWord, $itemWord) !== false || strpos($itemWord, $commandWord) !== false) {
                        $wordFound = true;
                        break;
                    }
                }
                if (!$wordFound) {
                    $allWordsMatch = false;
                    break;
                }
            }
            
            if ($allWordsMatch) {
                $hasExactMatch = true;
                $exactMatchItem = $itemName;
                break;
            }
        }
        
        if (!$hasExactMatch) {
            // If no exact match is found, show suggestions
            $suggestions = $this->findSimilarItems($command);
            if (!empty($suggestions)) {
                return [
                    'success' => false,
                    'message' => 'No exact match found. Did you mean one of these items?',
                    'suggestions' => $suggestions
                ];
            }
        }

        // If we have an exact match or no suggestions, proceed with GPT processing
        $messages = [
            [
                'role' => 'system',
                'content' => $this->systemMessage
            ],
            [
                'role' => 'user',
                'content' => $command
            ]
        ];

        $response = $this->openai->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages,
            'temperature' => 0.3
        ]);

        $parsedCommands = $this->parseCommands($response->choices[0]->message->content);
        
        // Validate each command has an exact item match
        foreach ($parsedCommands as &$cmd) {
            if (!in_array($cmd['item_name'], $this->itemNames)) {
                $suggestions = $this->findSimilarItems($cmd['item_name']);
                return [
                    'success' => false,
                    'message' => 'No exact match found. Did you mean one of these items?',
                    'suggestions' => $suggestions
                ];
            }
        }

        return [
            'success' => true,
            'commands' => $parsedCommands
        ];
    }
    
    public function executeCommand($parsedCommand) {
        try {
            // Validate required fields
            if (!isset($parsedCommand['item_name']) || !isset($parsedCommand['quantity']) || !isset($parsedCommand['action'])) {
                throw new Exception("Missing required fields in command");
            }
            
            // Find the item ID
            $stmt = $this->pdo->prepare("SELECT id FROM consumable_materials WHERE item_name = ?");
            $stmt->execute([$parsedCommand['item_name']]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$item) {
                throw new Exception("Item not found: " . $parsedCommand['item_name']);
            }
            
            // Prepare the form data
            $formData = [
                'consumable_material_id' => $item['id'],
                'quantity' => $parsedCommand['quantity'],
                'inventory_action' => $parsedCommand['action'],
                'employee_id' => $parsedCommand['employee_id'] ?? $_SESSION['user_id'] ?? 1, // Use parsed employee_id or fallback
            ];
            
            return $formData;
            
        } catch (Exception $e) {
            throw new Exception("Error executing command: " . $e->getMessage());
        }
    }

    public function processInventoryUpdate($formData) {
        try {
            // Get current inventory and item details
            $stmt = $this->pdo->prepare("
                SELECT cm.*, loc.location_name 
                FROM consumable_materials cm
                LEFT JOIN item_locations loc ON cm.normal_item_location = loc.id
                WHERE cm.id = ?
            ");
            $stmt->execute([$formData['consumable_material_id']]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$current) {
                throw new Exception("Item not found");
            }
            
            // Calculate new inventory
            $newInventory = $current['whole_quantity'];
            if ($formData['inventory_action'] === 'add') {
                $newInventory += $formData['quantity'];
            } else {
                $newInventory -= $formData['quantity'];
            }
            
            // Update inventory
            $stmt = $this->pdo->prepare("UPDATE consumable_materials SET whole_quantity = ? WHERE id = ?");
            $stmt->execute([$newInventory, $formData['consumable_material_id']]);
            
            // Record the transaction with all required fields
            $stmt = $this->pdo->prepare("
                INSERT INTO inventory_change_entries (
                    consumable_material_id,
                    item_short_code,
                    item_name,
                    item_description,
                    item_notes,
                    normal_item_location,
                    reorder_threshold,
                    items_added,
                    items_removed,
                    whole_quantity,
                    employee_id,
                    change_date
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
                )
            ");
            
            $stmt->execute([
                $formData['consumable_material_id'],
                $formData['item_short_code'],
                $current['item_name'],
                $current['item_description'],
                $formData['item_notes'] ?? '',
                $current['normal_item_location'],
                $formData['reorder_threshold'] ?? 0,
                $formData['inventory_action'] === 'add' ? $formData['quantity'] : 0,
                $formData['inventory_action'] === 'remove' ? $formData['quantity'] : 0,
                $newInventory,
                $formData['employee_id']
            ]);
            
            return [
                'success' => true,
                'item_id' => $formData['consumable_material_id']
            ];
        } catch (Exception $e) {
            throw new Exception("Error updating inventory: " . $e->getMessage());
        }
    }
}
?> 