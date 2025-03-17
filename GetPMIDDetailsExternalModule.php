<?php
namespace Vanderbilt\GetPMIDDetailsExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;


class GetPMIDDetailsExternalModule extends AbstractExternalModule
{
    function redcap_every_page_before_render($project_id){
        $instrument = $this->getProjectSetting('instrument-name');
        if($_REQUEST['page'] == $instrument && !empty($instrument)) {
            $record = (int)$_REQUEST['id'];
            $repeat_instance = (int)$_REQUEST['instance'];
            self:$this->getPMIDLink($project_id,$record,$repeat_instance);
        }
    }
    function redcap_survey_page($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance){
        $instrument_name = $this->getProjectSetting('instrument-name');
        if(($_REQUEST['page'] == $instrument || array_key_exists('s',$_REQUEST)) && !empty($instrument_name) && $instrument == $instrument_name) {
            self:$this->getPMIDLink($project_id, $record,$repeat_instance);
        }
    }
    public function getPMIDLink($project_id,$record,$repeat_instance){
        ## Adding the script messes the CSS. Adding styles again
        echo '<style>
                [data-mlm-type=label] {
                    font-family: "Open Sans", Helvetica, Arial, Helvetica, sans-serif !important;
                    font-size: 13px !important;
                    padding: 2px;
                    font-weight: bold !important;
                    padding-left: 5px;
                    padding-right: 5px;
                }
                #formSaveTip .btn-savedropdown{height: 25.8px}
              </style>
              <script>
                 function getLink(){
                    var value = document.getElementsByName("output_pmid")[0].value;
                    var url = '.json_encode($this->getUrl('getPMIDUrl.php')."&NOAUTH").';
                    var pid = '.json_encode($project_id).';
                    var record = '.json_encode($record).';
                    var instance = '.json_encode($repeat_instance).';
                    var redcap_csrf_token = '.json_encode($this->getCSRFToken()).';
                    if(value == ""){
                        alert("You need a PMID value to retrieve the data.")
                    }else{
                        document.querySelector("[name=\'output_pmid_btn\']").innerHTML = \'<i class="fa fa-spinner fa-spin"></i> Loading...\';
                        $.ajax({
                            type: "GET",
                            url: url,
                            data: "&pid="+pid+"&record="+record+"&instance="+instance+"&value="+value+"&redcap_csrf_token="+redcap_csrf_token,
                            error: function (xhr, status, error) {
                                alert(xhr.responseText);
                            },
                            success: function (result) {
                                jsonAjax = jQuery.parseJSON(result);
                                
                                document.querySelector("[name=\'output_pmid_btn\']").innerHTML = \'<i class="fa-solid fa-download"></i>  Get PMID Details\';
                      
                                if(jsonAjax.message == "success"){
                                  //Load data in form
                                   Object.keys(jsonAjax.data).forEach(function (label) {
                                           $("[name="+label+"]").val(jsonAjax.data[label]);
                                   });
                                }else{
                                    let message = document.querySelectorAll("[name=\'output_pmid_message\']");
                                    message.forEach(element => {
                                        element.remove();
                                    });
                                    
                                    const el = document.querySelector("[name=\'output_pmid_btn\']");
                                    var btn = \'<div class="alert alert-danger" name="output_pmid_message" style="margin-top:10px">\' +
                                             jsonAjax.message+
                                            \'</div>\';
                                    if(el != null)
                                        el.insertAdjacentHTML(\'afterend\', btn)
                                }
                            }
                        });
                    }
                }
                document.addEventListener("DOMContentLoaded",  function () { 
                    const el = document.querySelector("[name=\'output_pmid\']");
                    var btn = \'<div style="margin-top: 10px;float: right;">\' +
                                 \'<button type="button" name="output_pmid_btn" class="btn btn-xs fs14 btn-rcgreen button.fs14.btn-rcgreen" onclick="getLink()">\'+
                                    \'<i class="fa-solid fa-download"></i>\'+
                                    \' Get PMID Details\'+
                                 \'</button>\' +
                            \'</div>\';
                    if(el != null)
                        el.insertAdjacentHTML(\'afterend\', btn)
                });
        </script>';
    }
}



?>