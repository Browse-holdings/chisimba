<?php

echo '<h1>'.$this->objLanguage->languageText('mod_filemanager_demohowfileinputworks', 'filemanager', 'Demo on How the File Input Works').'</h1>';

$this->loadClass('form', 'htmlelements');
$this->loadClass('button', 'htmlelements');

$geshi = $this->getObject('geshiwrapper', 'wrapgeshi');
$geshi->language = 'php';
$geshi->source = '<?
$objSelectFile = $this->getObject(\'selectfile\', \'filemanager\');
$objSelectFile->name = \'nameofforminput\';
//$objSelectFile->restrictFileList = array(\'htm\');
//$objSelectFile->setDefaultFile(\'init_3788_1155254132\');
$form->addToForm($objSelectFile->show());
?>';

$geshi->startGeshi();

echo $geshi->show();

$objSelectFile = $this->getObject('selectfile', 'filemanager');
$objSelectFile->name = 'nameofforminput';
//$objSelectFile->restrictFileList = array('htm', 'jpg', 'flv');

if ($this->getParam('nameofforminput') != '') {
    $objSelectFile->setDefaultFile($this->getParam('nameofforminput'));
}

$form = new form ('selectfileform', $this->uri(array('action'=>'selecttest')));
$form->addToForm($objSelectFile->show());

$button = new button ('submitform', $this->objLanguage->languageText('word_submit', 'system', 'Submit'));
$button->setToSubmit();

$form->addToForm('<br />'.$button->show());

echo $form->show();

if ($this->getParam('nameofforminput') != '') {
    echo '<p>'.$this->objLanguage->languageText('mod_filemanager_yousubmitted', 'filemanager', 'You submitted').' : '.$this->getParam('nameofforminput').'</p>';
    $thisFile = $this->objFiles->getFile($this->getParam('nameofforminput'));
    
    if ($thisFile == FALSE) {
        echo $this->objLanguage->languageText('mod_filemanager_filedoesnotexistonsystem', 'filemanager', 'This file does not exist on the system');
    } else {
        echo $this->objLanguage->languageText('mod_filemanager_thisisfilebyfilenameof', 'filemanager', 'This is actually a file by the filename of').' '.$thisFile['filename'];
    }
}


$objSelectFile = $this->getObject('selectrealtimeimage', 'filemanager');
$objSelectFile->name = 'selectimage';

echo $objSelectFile->show();
?>
<script type="text/javascript">

function callFunctionFromParent()
{
    alert('fsaafs');
    alert(document.getElementById('hidden_selectimage').value);
}
</script>