<?php

use WHMCS\Module\Blazing\VpsPurchasePageCustomizations\Vendor\WHMCS\Module\Framework\ModuleHooks;
use WHMCS\Module\Blazing\VpsPurchasePageCustomizations\Vendor\WHMCS\Module\Framework\PageHooks\Client\AnyPageHook;
use WHMCS\Module\Blazing\VpsPurchasePageCustomizations\Vendor\WHMCS\Module\Framework\PageHooks\Client\CustomClientPageHook;
use WHMCS\Module\Blazing\VpsPurchasePageCustomizations\Vendor\WHMCS\Module\Framework\PageHooks\PageAdjustment\DomPageAdjustment;

require __DIR__ . '/bootstrap.php';

ModuleHooks::registerHooks(__FILE__, [
    CustomClientPageHook::buildInstance()
        ->setTemplate('ConfigureProduct')
        ->setPosition(AnyPageHook::POSITION_BODY_TOP)
        ->addJsPageAdjustment(DomPageAdjustment::build()
            ->setCssPath('form#frmConfigureProduct input[name=hostname]')
            ->setActionAddAfter('
                <div style="display:none; color: red;font-size: 13px;clear: both;" id="hosterr">
                    <b>Invalid hostname. Hostnames must contain only letters and numbers. No spaces or special characters.</b>
                </div>'))
        ->addJsPageAdjustment(DomPageAdjustment::build()
            ->setCssPath('form#frmConfigureProduct input[name=hostname]')
            ->setActionAddAfter('<small class="help-block" style="display: inline-block;">
                    <strong>Hostnames must have no spaces or special characters. Example: "Server1" is acceptable.
                    An uppercase letter, a lower case letter, a number or a special symbol</strong>                    
                </small>'))
        ->addJsPageAdjustment(DomPageAdjustment::build()
            ->setCssPath('form#frmConfigureProduct input[name=rootpw]')
            ->setActionAddAfter('
                <div style="display:none; color: red;font-size: 13px;clear: both;" id="passerr">
                    <b>Password must contain at least 10 characters and 3 of the following:  An uppercase letter, a lower case letter, a number or a special symbol</b>
                </div>'))
        ->addJsPageAdjustment(DomPageAdjustment::build()
            ->setCssPath('form#frmConfigureProduct input[name=rootpw]')
            ->setActionAddAfter('<small class="help-block" style="display: inline-block;">
                    <strong>Password must contain at least 3 of the following:</strong>
                    An uppercase letter, a lower case letter, a number or a special symbol
                </small>'))
        ->addJsPageAdjustment(DomPageAdjustment::build()
            ->setCssPath('form#frmConfigureProduct input[name=ns1prefix] <<')
            ->setActionHideNode())
        ->addJsPageAdjustment(DomPageAdjustment::build()
            ->setCssPath('form#frmConfigureProduct input[name=ns1prefix]')
            ->setActionSetProperty('value', 'ns1'))
        ->addJsPageAdjustment(DomPageAdjustment::build()
            ->setCssPath('form#frmConfigureProduct input[name=ns2prefix] <<')
            ->setActionHideNode())
        ->addJsPageAdjustment(DomPageAdjustment::build()
            ->setCssPath('form#frmConfigureProduct input[name=ns2prefix]')
            ->setActionSetProperty('value', 'ns2'))
        ->setCodeCallback(function() {
            return <<<CODE
                <script type="text/javascript">
                jQuery.fn.bindUp = function (type, parameters, fn) {
                    type = type.split(/\s+/);
                
                    this.each(function () {
                        var len = type.length;
                        while (len--) {
                            if (typeof parameters === "function")
                                jQuery(this).bind(type[len], parameters);
                            else
                                jQuery(this).bind(type[len], parameters, fn);
                
                            var evt = jQuery._data(this, 'events')[type[len]];
                            evt.splice(0, 0, evt.pop());
                        }
                    });
                };
                </script>
                
                <script type="text/javascript">
                // One of those 3 should work (bulletproof)
                $('#btnCompleteProductConfig').bindUp('click', function(e) {
                    var result = checkForm($('#frmConfigureProduct')[0]);
                
                    if (!result) {
                        e.stopImmediatePropagation();
                    }
                    
                    return result;
                });
                $('#btnCompleteProductConfig').bindUp('click', function(e) {
                    return checkForm($('#frmConfigureProduct')[0]);
                });
                $('#btnCompleteProductConfig')[0].onclick = function() {
                    return checkForm($('#frmConfigureProduct')[0]);
                };
                
                function checkForm(form)
                {
                
                    var hosterr="false";
                    var passerr="false";
                
                    if(!(form.hostname.value != "" && form.hostname.value)) {
                        hosterr="true";
                    }
                
                    var re = /^[a-zA-Z0-9\-_]+$/;
                    // validation fails if the input doesn't match our regular expression
                    if(!re.test(form.hostname.value)) {
                        hosterr="true";
                    }
                
                    if(hosterr=="true") {
                        document.getElementById('hosterr').style.display="block";
                        form.hostname.focus();
                        return false;
                    } else {
                        document.getElementById('hosterr').style.display="none";
                    }
                
                    if(form.rootpw.value != "" && form.rootpw.value) {
                        if(form.rootpw.value.length < 10) {
                            passerr="true";
                        }
                        re = /[0-9]|[^a-zA-Z0-9\-\/]/;
                        if(!re.test(form.rootpw.value)) {
                            passerr="true";
                        }
                        re = /[a-z]/;
                        if(!re.test(form.rootpw.value)) {
                            passerr="true";
                        }
                        re = /[A-Z]/;
                        if(!re.test(form.rootpw.value)) {
                            passerr="true";
                        }
                
                
                
                
                    } else {
                        passerr="true";
                    }
                
                    if(passerr=="true") {
                        document.getElementById('passerr').style.display="block";
                        form.rootpw.focus();
                        return false;
                    } else {
                        document.getElementById('passerr').style.display="none";
                    }
                
                    return true;
                }                  
                
                </script>
CODE;

        })
        ->convertToCallbackHook()
]);