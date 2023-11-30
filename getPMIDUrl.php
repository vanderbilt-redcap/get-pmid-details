<?php
namespace Vanderbilt\GetPMIDDetailsExternalModule;

$record = (int)$_REQUEST['record'];
$pmid = (int)$_REQUEST['value'];
$pid = (int)$_REQUEST['pid'];
$instance = (int)$_REQUEST['instance'];

$Proj = new \Project($pid);
$event_id = $Proj->firstEventId;

$api_key = $module->getProjectSetting('api-key');
$instrument = $module->getProjectSetting('instrument-name');
$url = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esummary.fcgi?db=pubmed&retmode=json";
if(isset($api_key)){
    $url .= "&api_key=".$api_key;
}
if(isset($pmid)){
    $url .= "&id=".$pmid;
}

#Get the JSON
$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL, $url);
$result = curl_exec($ch);
curl_close($ch);

$pmid_data = json_decode($result,true);
if(empty($pmid_data["error"])) {
    $authors = "";
    foreach ($pmid_data['result'][$pmid]['authors'] as $data) {
        $authors .= $data["name"] . ", ";
    }
    $pmcid = "";
    foreach ($pmid_data['result'][$pmid]['articleids'] as $label => $value) {
        if ($value['idtype'] == "pmc") {
            $pmcid = $value['value'];
            break;
        }
    }
    $authors = rtrim($authors, ", ");

    #DATA
    $array['output_type'] = 1;
    $array['output_title'] = $pmid_data['result'][$pmid]['title'];
    $array['output_year'] = $pmid_data['result'][$pmid]['pubdate'];
    $array['output_authors'] = $authors;
    $array['output_venue'] = $pmid_data['result'][$pmid]['source'];
    $array['output_citation'] = $pmid_data['result'][$pmid]['source'] . ", " . $pmid_data['result'][$pmid]['epubdate'];
    $array['output_pmid'] = $pmid;
    $array['output_pmcid'] = $pmcid;
    $array['output_url'] = "https://pubmed.ncbi.nlm.nih.gov/" . $pmid;
    $array[$instrument.'_complete'] = 2;

    $isRepeating = false;
    $q = $module->query("SELECT form_name FROM redcap_events_repeat where event_id=?",[$event_id]);
    while ($row = $q->fetch_assoc()) {
        $form_name = $row['form_name'];
        if($instrument == $form_name){
            $isRepeating = true;
            break;
        }
    }
    $array_data = array();
    if($isRepeating) {
        $array_data[$record]['repeat_instances'][$event_id][$instrument][$instance] = $array;
    }else{
        $array_data[$record][$event_id] = $array;
    }

    $results = \Records::saveData($pid, 'array', $array_data,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
    error_log(json_encode($results,JSON_PRETTY_PRINT));

    if(empty($results['errors'])){
        echo json_encode(array("message"=>"success"));
    }else {
        echo json_encode(array("message" => "Something went wrong when saving the data"));
    }
}else{
    echo json_encode(array("message" => "There are 0 results for PIMD #".$pmid));
}

?>