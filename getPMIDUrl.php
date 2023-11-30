<?php
namespace Vanderbilt\GetPMIDDetailsExternalModule;

$record = (int)$_REQUEST['record'];
$pmid = (int)$_REQUEST['value'];
$pid = (int)$_REQUEST['pid'];

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
    $array = array(array(
        'record_id' => $record,
        'output_title' => $pmid_data['result'][$pmid]['title'],
        'output_year' => $pmid_data['result'][$pmid]['pubdate'],
        'output_authors' => $authors,
        'output_venue' => $pmid_data['result'][$pmid]['source'],
        'output_citation' => $pmid_data['result'][$pmid]['source'] . ", " . $pmid_data['result'][$pmid]['epubdate'],
        'output_pmid' => $pmid,
        'output_pmcid' => $pmcid,
        'output_url' => "https://pubmed.ncbi.nlm.nih.gov/" . $pmid,
        $instrument.'_complete' => 2
    ));
    $jsonRM = json_encode($array);
    $results = \Records::saveData($pid, 'json', $jsonRM, 'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);

    if(empty($results['errors'])){
        echo json_encode(array("message"=>"success"));
    }else {
//        echo json_encode(array("message" => "Something went wrong when saving the data"));
        echo json_encode(array("message" =>json_encode($results,JSON_PRETTY_PRINT)));
    }
}else{
    echo json_encode(array("message" => "There are 0 results for PIMD #".$pmid));
}

?>