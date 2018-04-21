{if !empty($message)}
    {if $message['result']}
        <div class="successbox">
            <strong>
                <span class="title">Success</span>
            </strong>
            <br>{$message['msg']}
        </div>
    {else}
        <div class="errorbox">
            <strong>
                <span class="title">Error</span>
            </strong>
            <br>{$message['msg']}
        </div>
    {/if}
{/if}

<ul class="nav nav-tabs client-tabs" role="tablist">
    <li class="{if $tab == 'paymentslist'}active{else}tab{/if}"><a href="{$vars['modulelink']}&tab=paymentslist" id="clientTab-1">Payments</a></li>
    <li class="{if $tab == 'blacklist'}active{else}tab{/if}"><a href="{$vars['modulelink']}&tab=blacklist" id="clientTab-5">Email domain blacklist</a></li>
</ul>
<div class="tab-content client-tabs">
    <div class="tab-pane active" id="profileContent">
        {if $tab == 'paymentslist'}
            <table cellspacing="0" cellpadding="3" width="100%" border="0">
                <tbody>
                    <tr>
                        <td width="33%" align="left">{$paginator['total']} Records Found, Page {$paginator['current_page']} of {$paginator['last_page']}</td>
                        <td width="33%" align="center">
                            <a href="{$vars['modulelink']}{if !empty($orderby)}&orderby={$orderby}{/if}{if !empty($priority)}&priority={$priority}{/if}&page={$paginator['current_page']}{if $blacklist == 'on'}&blacklist=off{else}&blacklist=on{/if}{if $uniquefilter == 'on'}&uniquefilter=on{else}&uniquefilter=off{/if}" class="btn {if $blacklist == 'on'}btn-success{else}btn-default{/if}" title="{if $blacklist == 'on'}Cancel blacklist{else}Apply blacklist{/if} email domains">{if $blacklist == 'on'}Cancel blacklist{else}Apply blacklist{/if}</a>
                            <a href="{$vars['modulelink']}{if !empty($orderby)}&orderby={$orderby}{/if}{if !empty($priority)}&priority={$priority}{/if}&page={$paginator['current_page']}{if $blacklist == 'on'}&blacklist=on{else}&blacklist=off{/if}{if $uniquefilter == 'on'}&uniquefilter=off{else}&uniquefilter=on{/if}" class="btn {if $uniquefilter == 'on'}btn-success{else}btn-default{/if}" title="{if $uniquefilter == 'on'}Cancel unique email{else}Apply unique email{/if} filter">{if $uniquefilter == 'on'}Cancel unique filter{else}Apply unique filter{/if}</a>
                        </td>
                        <td width="34%" align="right">
                            <form method="post" action="{$vars['modulelink']}{if !empty($orderby)}&orderby={$orderby}{/if}{if !empty($priority)}&priority={$priority}{/if}&page={$paginator['current_page']}{if $blacklist == 'on'}&blacklist=on{else}&blacklist=off{/if}{if $uniquefilter == 'on'}&uniquefilter={$uniquefilter}{/if}">
                                <input type="hidden" name="action" value="gotopage">
                                Jump to Page: <select name="page" onchange="submit()">
                                    {for $p=1 to $paginator['last_page']}
                                    <option value="{$p}" {if $p == $paginator['current_page']}selected=""{/if}>{$p}</option>
                                    {/for}
                                </select> <input value="Go" class="btn btn-xs btn-default" type="submit">
                            </form>
                        </td>
                    </tr>
                </tbody>
            </table>
            <br>
            <table class="form" width="100%">
                <tbody><tr><td colspan="2" class="fieldarea" style="text-align:center;"><strong>Large payments</strong></td></tr>
                <tr><td align="center">
                        <div class="tablebg">
                            <table class="datatable filterable" cellspacing="1" cellpadding="3" width="100%" border="0">
                                <tbody><tr>
                                    <th><a href="{$vars['modulelink']}&orderby=invoiceid&priority={if $priority == 'DESC'}ASC{else}DESC{/if}&page={$paginator['current_page']}{if $blacklist == 'on'}&blacklist=on{else}&blacklist=off{/if}{if $uniquefilter == 'on'}&uniquefilter=on{else}&uniquefilter=off{/if}">Invoice ID {if $orderby == 'invoiceid'}{if $priority == 'DESC'}&darr;{else}&uarr;{/if}{/if}</a></th>
                                    <th><a href="{$vars['modulelink']}&orderby=name&priority={if $priority == 'DESC'}ASC{else}DESC{/if}&page={$paginator['current_page']}{if $blacklist == 'on'}&blacklist=on{else}&blacklist=off{/if}{if $uniquefilter == 'on'}&uniquefilter=on{else}&uniquefilter=off{/if}">Name {if $orderby == 'name'}{if $priority == 'DESC'}&darr;{else}&uarr;{/if}{/if}</a></th>
                                    <th><a href="{$vars['modulelink']}&orderby=email&priority={if $priority == 'DESC'}ASC{else}DESC{/if}&page={$paginator['current_page']}{if $blacklist == 'on'}&blacklist=on{else}&blacklist=off{/if}{if $uniquefilter == 'on'}&uniquefilter=on{else}&uniquefilter=off{/if}">Email {if $orderby == 'email'}{if $priority == 'DESC'}&darr;{else}&uarr;{/if}{/if}</a></th>
                                    <th>PayPal Email</th>
                                    <th><a href="{$vars['modulelink']}&orderby=amount&priority={if $priority == 'DESC'}ASC{else}DESC{/if}&page={$paginator['current_page']}{if $blacklist == 'on'}&blacklist=on{else}&blacklist=off{/if}{if $uniquefilter == 'on'}&uniquefilter=on{else}&uniquefilter=off{/if}">Amount {if $orderby == 'amount'}{if $priority == 'DESC'}&darr;{else}&uarr;{/if}{/if}</a></th>
                                    <th><a href="{$vars['modulelink']}&orderby=datepaid&priority={if $priority == 'DESC'}ASC{else}DESC{/if}&page={$paginator['current_page']}{if $blacklist == 'on'}&blacklist=on{else}&blacklist=off{/if}{if $uniquefilter == 'on'}&uniquefilter=on{else}&uniquefilter=off{/if}">Timestamp {if $orderby == 'datepaid'}{if $priority == 'DESC'}&darr;{else}&uarr;{/if}{/if}</a></th>
                                </tr>
                                {foreach from=$invoices item=invoice}
                                    <tr style="">
                                        <td><a href="invoices.php?action=edit&id={$invoice->id}" target="_blank">{$invoice->id}</a></td>
                                        <td>{$invoice->firstname} {$invoice->lastname}</td>
                                        <td>{$invoice->email}</td>
                                        <td>null</td>
                                        <td>{$invoice->total}</td>
                                        <td>{$invoice->datepaid}</td>
                                    </tr>
                                {/foreach}
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
            <ul class="pager">
                <li class="previous {if $paginator['current_page'] <= 1}disabled{/if}">
                    <a href="{if $paginator['current_page'] <= 1}#!{else}{$vars['modulelink']}{if !empty($orderby)}&orderby={$orderby}{/if}{if !empty($priority)}&priority={$priority}{/if}&page={($paginator['current_page'] - 1)}{/if}{if $blacklist == 'on'}&blacklist={$blacklist}{/if}{if $uniquefilter == 'on'}&uniquefilter={$uniquefilter}{/if}">
                        « Previous Page
                    </a>
                </li>
                <li class="next {if $paginator['current_page'] >= $paginator['last_page']}disabled{/if}">
                    <a href="{if $paginator['current_page'] >= $paginator['last_page']}#!{else}{$vars['modulelink']}{if !empty($orderby)}&orderby={$orderby}{/if}{if !empty($priority)}&priority={$priority}{/if}&page={($paginator['current_page'] + 1)}{/if}{if $blacklist == 'on'}&blacklist={$blacklist}{if $uniquefilter == 'on'}&uniquefilter={$uniquefilter}{/if}{/if}">
                        Next Page »
                    </a>
                </li>
            </ul>
        {elseif $tab == 'blacklist'}
            <div align="center">
                <form method="post" action="{$vars['modulelink']}&tab=blacklist&page={$paginator['current_page']}">
                    <input type="text" placeholder="email domain" name="emaildomain">
                    <input type="hidden" name="action" value="addemaildomain">
                    <input value="Add" class="button btn btn-default" type="submit">
                </form>
            </div>
            <table cellspacing="0" cellpadding="3" width="100%" border="0">
                <tbody>
                <tr>
                    <td width="100%" align="left">{$paginator['total']} Records Found, Page {$paginator['current_page']} of {$paginator['last_page']}</td>
                </tbody>
            </table>
            <br>
            <table class="form" width="100%">
                <tbody>
                    <tr><td align="center">
                            <div class="tablebg">
                                <table class="datatable filterable" cellspacing="1" cellpadding="3" width="100%" border="0">
                                    <tbody>
                                        <tr>
                                            <th>ID</th>
                                            <th>Domain</th>
                                            <th>Actions</th>
                                         </tr>
                                        {foreach from=$domains item=domain}
                                            <tr style="">
                                                <td>{$domain->id}</td>
                                                <td>{$domain->domain}</td>
                                                <td class="text-center">
                                                    <form method="post" name="domaindelete{$domain->id}" action="{$vars['modulelink']}&tab=blacklist&page={$paginator['current_page']}">
                                                        <span onClick="if(confirm('You are sure?')) document.forms.domaindelete{$domain->id}.submit()">
                                                            <img src="images/delete.gif" alt="Cancel &amp; Delete" width="16" height="16" border="0">
                                                        </span>
                                                        <input type="hidden" name="action" value="domaindelete">
                                                        <input type="hidden" name="id" value="{$domain->id}">
                                                    </form>
                                                </td>
                                            </tr>
                                        {foreachelse}
                                            <div class="gracefulexit">
                                                No payments found for your criteria
                                            </div>
                                        {/foreach}
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <ul class="pager">
                <li class="previous {if $paginator['current_page'] <= 1}disabled{/if}">
                    <a href="{if $paginator['current_page'] <= 1}#!{else}{$vars['modulelink']}&tab=blacklist&page={($paginator['current_page'] - 1)}{/if}">
                        « Previous Page
                    </a>
                </li>
                <li class="next {if $paginator['current_page'] >= $paginator['last_page']}disabled{/if}">
                    <a href="{if $paginator['current_page'] >= $paginator['last_page']}#!{else}{$vars['modulelink']}&tab=blacklist&page={($paginator['current_page'] + 1)}{/if}">
                        Next Page »
                    </a>
                </li>
            </ul>
        {/if}
    </div>
</div>