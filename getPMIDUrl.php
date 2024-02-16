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

    $year = $pmid_data['result'][$pmid]['pubdate'];
    if ($year == trim($year) && strpos($year, ' ') !== false) {
        #Date has spaces but not in beginning or end
        $year = explode(" ",$pmid_data['result'][$pmid]['pubdate'])[0];
    }

    if (is_numeric($year)) {
        $array['output_year'] = $year;
    }

    $title = $pmid_data['result'][$pmid]['title'];
    if(substr($title, -1) == "."){
        $title = rtrim($title, ".");
    }

    ## CITATION -> {source}. {epub date or pub date}; {volume}({issue}):{pages}
    $citation = $pmid_data['result'][$pmid]['source'];
    if(!empty($citation)){
        $citation .= ". ";
    }
    if(empty($pmid_data['result'][$pmid]['epubdate'])){
        $citation .= $pmid_data['result'][$pmid]['pubdate'];
    }else{
        $citation .= $pmid_data['result'][$pmid]['epubdate'];
    }
    if(!empty($pmid_data['result'][$pmid]['volume'])){
        $citation .= "; ".$pmid_data['result'][$pmid]['volume'];
    }
    if(!empty($pmid_data['result'][$pmid]['issue'])){
        $citation .= "(".$pmid_data['result'][$pmid]['issue'].")";
    }
    if(!empty($pmid_data['result'][$pmid]['pages'])){
        $citation .= ":".$pmid_data['result'][$pmid]['pages'];
    }

    #DATA
    $array['output_type'] = 1;
    $array['output_title'] = $title;
    $array['output_authors'] = $authors;
    $array['output_venue'] = $pmid_data['result'][$pmid]['source'];
    $array['output_citation'] = $citation;
    $array['output_pmid'] = $pmid;
    $array['output_pmcid'] = $pmcid;
    $array['output_url'] = "https://pubmed.ncbi.nlm.nih.gov/" . $pmid;
    $array[$instrument.'_complete'] = 2;

    echo json_encode(array("message"=>"success","data"=>$array));
}else{
    echo json_encode(array("message" => "There are 0 results for PIMD #".$pmid));
}

?>