<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
    <tr>
        <td class="fieldlabel">Email</td>
        <td class="fieldarea">
            <form method="post" action="<?php echo $vars['modulelink']; ?>">
                <input type="text" size="25" name="email" placeholder="email" autocomplete="off" class="form-control input-300 input-inline">
                <input type="text" size="30" name="description" placeholder="reason" autocomplete="off" class="form-control input-400 input-inline">
                <input type="submit" value="Add to Ban"
                       class="btn btn-danger" role="button"
                       aria-disabled="false">
                <input type="hidden" name="action" value="banemail">
            </form>
        </td>
        <td class="fieldarea">
            <form method="post" action="<?php echo $vars['modulelink']; ?>">
                <input type="text" name="email" class="form-control input-300 input-inline" placeholder="email" autocomplete="off"
                       value="<?php isset($old['search_email']) ? print($old['search_email']) : print(''); ?>">
                <input type="submit" value="Search"
                       class="btn btn-primary" role="button"
                       aria-disabled="false">
                <input type="hidden" name="action" value="searchemail">
            </form>
        </td>
    </tr>
</table>

<table width="100%" cellspacing="0" cellpadding="3" border="0">
    <tbody>
    <tr>
        <td width="50%" align="left"><?php echo($data->total) ?> Records Found, Page <?php echo($data->current_page) ?>
            of <?php echo($data->last_page) ?></td>
        <td width="50%" align="right">Jump to Page:
            <form method="post" action="<?php echo $vars['modulelink'] ?>">
                <select name="page" onchange="submit()">
                    <?php
                    for ($i = 1; $i <= (int)($data->total / $data->per_page); $i++) {
                        echo '<option value="' . $i . '"';
                        if ($i == $data->current_page) echo 'selected=""';
                        echo '>' . $i . '</option>';
                    }
                    ?>
                </select>
                <input value="Go" class="btn btn-xs btn-default" type="submit">
            </form>
        </td>
    </tr>
    </tbody>
</table>

<div class="tablebg">
    <table id="sortabletbl0" class="datatable" width="100%" cellspacing="1" cellpadding="3" border="0">
        <tbody>
        <tr>
            <!--<th width="20"><input id="checkall0" type="checkbox"></th>-->
            <th><a href="/admin/invoices.php?orderby=email">Email</a></th>
            <th>Reason</th>
            <th>Date</th>
            <th width="20"></th>
        </tr>
        <?php foreach ($data->data as $value) {
            echo '<tr class="text-center">
           <!-- <td><input name="selectedinvoices[]" value="7" class="checkall" type="checkbox"></td>-->
            <td>' . $value->user_email . '</td>
            <td><span>' . (empty($value->description) ? '' : $value->description) . '</span></td>
            <td>' . $value->created_at . '</td>
            <td><form method="post" action="' . $vars['modulelink'] . '">
                    <input type="hidden" name="delete_id" value="' . $value->id . '">
                    <input type="hidden" name="action" value="deletemail">
                    <a href="#" onclick="if(confirm(\'Want to delete?\')) $(this).closest(\'form\').submit(); else return false;" title="Delete">
                        <img src="images/delete.gif" alt="Delete" width="16" border="0" height="16">
                    </a>
                </form>
            </td>
        </tr>';
        }
        ?>
        </tbody>
    </table>
</div>

<ul class="pager">
    <li class="previous <?php if (is_null($data->prev_page_url)) print('disabled') ?>">
        <a href="<?php is_null($data->prev_page_url) ? print('#') : print($vars['modulelink'] . '&page=' . ($data->current_page - 1)) ?>">
            «Previous Page
        </a>
    </li>
    <li class="next <?php if (is_null($data->next_page_url)) print('disabled') ?>">
        <a href="<?php is_null($data->next_page_url) ? print('#') : print($vars['modulelink'] . '&page=' . ($data->current_page + 1)) ?>">
            Next Page »
        </a>
    </li>
</ul>