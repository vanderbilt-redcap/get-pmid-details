<?php
namespace Vanderbilt\GetPMIDDetailsExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;


class GetPMIDDetailsExternalModule extends AbstractExternalModule
{

    function redcap_every_page_before_render($project_id){
        $instrument = $this->getProjectSetting('instrument-name');
        if($_REQUEST['page'] == $instrument) {
            $record = (int)$_REQUEST['id'];
            self:$this->getPMIDLink($project_id,$record);
        }
    }

    function redcap_survey_page($project_id,$record){
        $instrument = $this->getProjectSetting('instrument-name');
        if($_REQUEST['page'] == $instrument) {
            self:$this->getPMIDLink($project_id, $record);
        }
    }

    function getPMIDLink($project_id,$record){
        echo '<script type="text/javascript" src="'.$this->getUrl('js/jquery-3.3.1.min.js').'"></script>';
        echo '<script>
                function getLink(){
                    var value = document.getElementsByName("output_pmid")[0].value;
                    var url = '.json_encode($this->getUrl('getPMIDUrl.php')).';
                    var pid = '.json_encode($project_id).';
                    var record = '.json_encode($record).';
                    var redcap_csrf_token = '.json_encode($this->getCSRFToken()).';
                    if(value == ""){
                        alert("You need a PMID value to retrieve the data.")
                    }else{
                        document.querySelector("[name=\'output_pmid_btn\']").innerHTML = \'<i class="fa fa-spinner fa-spin"></i> Loading...\';
                        $.ajax({
                            type: "GET",
                            url: url,
                            data: "&pid="+pid+"&record="+record+"&value="+value+"&redcap_csrf_token="+redcap_csrf_token,
                            error: function (xhr, status, error) {
                                alert(xhr.responseText);
                            },
                            success: function (result) {
                                jsonAjax = jQuery.parseJSON(result);
                                
                                document.querySelector("[name=\'output_pmid_btn\']").innerHTML = \'<i class="fa-solid fa-download"></i>  Get PMID Details\';
                      
                                if(jsonAjax.message == "success"){
                                    window.location.reload();
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
                                 \'<button type="button" name="output_pmid_btn" class="btn btn-xs fs14 btn-rcgreen" onclick="getLink()">\'+
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