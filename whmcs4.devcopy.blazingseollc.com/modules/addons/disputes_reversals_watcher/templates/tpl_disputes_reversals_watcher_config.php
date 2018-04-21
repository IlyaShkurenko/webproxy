<table width="100%" cellspacing="0" cellpadding="3" border="0">
    <tbody>
    <tr>
        <td width="50%" align="left"><?php echo($data['total']) ?> Records Found, Page <?php echo($data['current_page']) ?>
            of <?php echo($data['last_page']) ?></td>
        <td width="50%" align="right">Jump to Page:
            <form method="post" action="<?php echo $vars['modulelink'] ?>">
                <select name="page">
                    <?php
                    for ($i = 1; $i <= $data['last_page']; $i++) {
                        echo '<option onclick="window.location.replace(\'' . $systemUrl . '/admin/' . $vars['modulelink'] . '&page=' . $i . '\');" value="' . $i . '"';
                        if ($i == $data['current_page']) echo 'selected=""';
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
            <th>Info</th>
            <th>Date</th>
            <th>IP</th>
        </tr>
        <?php foreach ($data['data'] as $value) {
            echo '<tr class="text-center">
            <td><pre>' . $value->description . '</pre></td>
            <td>' . $value->date . '</td>
            <td>' . $value->ipaddr . '</td>
        </tr>';
        }
        ?>
        </tbody>
    </table>
</div>

<ul class="pager">
    <li class="previous <?php if ($data['current_page'] <= 1) print('disabled') ?>">
        <a href="<?php $data['current_page'] <= 1 ? print('#') : print($vars['modulelink'] . '&page=' . ($data['current_page'] - 1)) ?>">
            « Previous Page
        </a>
    </li>
    <li class="next <?php if ($data['current_page'] >= $data['last_page']) print('disabled') ?>">
        <a href="<?php $data['current_page'] >= $data['last_page'] ? print('#') : print($vars['modulelink'] . '&page=' . ($data['current_page'] + 1)) ?>">
            Next Page »
        </a>
    </li>
</ul>