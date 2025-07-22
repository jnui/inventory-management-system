<?php
// consumable_list_inline.php
// Inline editing page for consumable_materials (admin only)
// --------------------------------------------------------
// Access: admins only

require_once 'auth_check.php';
require_admin();               // redirect non-admins
require_once 'db_connection.php';

// Fetch consumable materials (trimmed columns)
try {
    $stmt = $pdo->query("SELECT id,
                                item_type,
                                item_name,
                                item_description,
                                diameter,
                                normal_item_location,
                                whole_quantity,
                                item_units_whole,
                                item_units_part,
                                qty_parts_per_whole,
                                reorder_threshold,
                                composition_description,
                                vendor
                         FROM consumable_materials
                         ORDER BY item_name ASC");
    $consumables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('DB error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Consumables – Inline Admin Edit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- jQuery + Bootstrap + DataTables -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link   href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <link   href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

    <!-- DataTables FixedHeader CSS -->
    <link href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.dataTables.min.css" rel="stylesheet">
    <!-- DataTables FixedHeader JS -->
    <script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>

    <style>
        body { padding: 70px 10px 20px; }
        .editable { cursor: text; }
        .editable:hover { background: #fffceb; }
        .updating { opacity: 0.6; }
        .flash-success { background: #d4edda !important; }
        .flash-error   { background: #f8d7da !important; }
    </style>
</head>
<body>
<?php $page_title = 'Inline Edit Consumables'; include 'nav_template.php'; ?>
<div class="container-fluid">
    <h3 class="mb-3">Consumable Materials – Inline Editing</h3>
    <p class="text-muted">Double-click a cell (or press Enter after typing) to commit changes immediately. ESC cancels.</p>
    <table id="consumablesInlineTable" class="display nowrap" style="width:100%">
        <thead>
        <tr>
            <th>ID</th>
            <th>Type</th>
            <th>Name</th>
            <th>Description</th>
            <th>Diameter</th>
            <th>Location</th>
            <th>Qty</th>
            <th>Units-Whole</th>
            <th>Units-Part</th>
            <th>Parts/Whole</th>
            <th>Re-order</th>
            <th>Material</th>
            <th>Vendor</th>
        </tr>
        </thead>
    </table>
</div>

<script>
$(function(){
    const dataSet = <?php echo json_encode($consumables); ?>;

    // helper to attach editable behaviour
    function makeEditable(columnName){
        return function(td, cellData, rowData){
            $(td).attr('contenteditable', true)
                 .addClass('editable')
                 .data('column', columnName)
                 .data('id', rowData.id);
        };
    }

    const table = $('#consumablesInlineTable').DataTable({
        data: dataSet,
        scrollX: true,
        columns: [
            { data:'id',  visible:true, orderable:true },                       // ID non-editable
            { data:'item_type', createdCell: makeEditable('item_type') },
            { data:'item_name', createdCell: makeEditable('item_name') },
            { data:'item_description', createdCell: makeEditable('item_description') },
            { data:'diameter', createdCell: makeEditable('diameter') },
            { data:'normal_item_location', createdCell: makeEditable('normal_item_location') },
            { data:'whole_quantity', createdCell: makeEditable('whole_quantity') },
            { data:'item_units_whole', createdCell: makeEditable('item_units_whole') },
            { data:'item_units_part', createdCell: makeEditable('item_units_part') },
            { data:'qty_parts_per_whole', createdCell: makeEditable('qty_parts_per_whole') },
            { data:'reorder_threshold', createdCell: makeEditable('reorder_threshold') },
            { data:'composition_description', createdCell: makeEditable('composition_description') },
            { data:'vendor', createdCell: makeEditable('vendor') }
        ],
        order:[[2,'asc']],
        paging:false,
        dom:'rtip',
        fixedHeader: {
            header: true,
            headerOffset: 60    // height of your fixed navbar
        },
    });

    // delegated events for editing
    const tbl = $('#consumablesInlineTable');

    tbl.on('focus', '.editable', function(){
        $(this).data('original', $(this).text().trim());
    });

    tbl.on('keydown', '.editable', function(e){
        if(e.key==='Enter'){
            e.preventDefault();
            $(this).blur();
        } else if(e.key==='Escape'){
            e.preventDefault();
            $(this).text($(this).data('original'));
            $(this).blur();
        }
    });

    tbl.on('blur', '.editable', function(){
        const cell = $(this);
        const newVal = cell.text().trim();
        const orig = cell.data('original');
        if(newVal === orig){ return; }

        cell.addClass('updating');

        $.post('update_consumable.php', {
            id: cell.data('id'),
            column: cell.data('column'),
            value: newVal
        }, function(resp){
            if(resp.status === 'ok'){
                cell.removeClass('updating').addClass('flash-success');
                table.cell(cell).data(newVal);
                setTimeout(()=>cell.removeClass('flash-success'), 800);
            } else {
                cell.text(orig);
                cell.removeClass('updating').addClass('flash-error');
                setTimeout(()=>cell.removeClass('flash-error'), 800);
                alert(resp.msg || 'Update failed');
            }
        }, 'json').fail(function(){
            cell.text(orig);
            cell.removeClass('updating').addClass('flash-error');
            setTimeout(()=>cell.removeClass('flash-error'), 800);
            alert('Server error');
        });
    });
});
</script>
</body>
</html> 