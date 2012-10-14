<?php
$this->loadClass('link', 'htmlelements');
$this->loadClass('form', 'htmlelements');
$this->loadClass('textinput', 'htmlelements');
$this->loadClass('hiddeninput', 'htmlelements');
$this->loadClass('button', 'htmlelements');
$this->loadClass('htmlheading', 'htmlelements');
$searchForm = new form('filesearch', $this->uri(array('action' => 'search')));
$searchForm->method = 'GET';
$hiddenInput = new hiddeninput('module', 'filemanager');
$searchForm->addToForm($hiddenInput->show());
$hiddenInput = new hiddeninput('action', 'search');
$searchForm->addToForm($hiddenInput->show());
$textinput = new textinput('filequery', $this->getParam('filequery'));
$searchForm->addToForm($textinput->show());
$button = new button('search', $this->objLanguage->languageText('word_search', 'system', 'Search'));
$button->setToSubmit();
$searchForm->addToForm($button->show());
// Create an Instance of the CSS Layout
$cssLayout = $this->newObject('csslayout', 'htmlelements');
$objFolders = $this->getObject('dbfolder');
$objQuotas = $this->getObject('dbquotas');
$header = new htmlheading();
$header->type = 2;
$header->str = $this->objLanguage->languageText('mod_filemanager_name', 'filemanager', 'File Manager');
$leftColumn = $header->show();
$tagCloudLink = new link($this->uri(array('action' => 'tagcloud')));
$tagCloudLink->link = 'Tag Cloud';
$leftColumn .= $searchForm->show() . '<ul><li>' . $tagCloudLink->show() . '</li>';
if ($objUser->isAdmin()) {
    $quotaManager = new link($this->uri(array('action' => 'quotamanager')));
    $quotaManager->link = $this->objLanguage->languageText('mod_filemanager_quotamanager', 'filemanager', 'Quota Manager');
    $leftColumn .= '<li>' . $quotaManager->show() . '</li>';
}
$leftColumn .= '</ul>';
if (!isset($folderId)) {
    $folderId = '';
}
if ($this->contextCode != '' && $this->getParam('context') != 'no') {
    $leftColumn .= '<div class="filemanagertree">' . $objFolders->getTree('context', $this->contextCode, 'dhtml', $folderId) . $objQuotas->getQuotaGraph('context/' . $this->contextCode) . '</div>';
    $leftColumn .= '<br /><br /><br />';
}
//$leftColumn .= $objFolders->showUserFolders($folderId);
$leftColumn .= '<div class="filemanagertree">' . $objFolders->getTree('users', $this->objUser->userId(), 'dhtml', $folderId) . $objQuotas->getQuotaGraph('users/' . $this->objUser->userId()) . '</div>';
$leftColumn = "<div class='filemanager_left'>$leftColumn</div>";
$cssLayout->setLeftColumnContent($leftColumn);


// Set the Content of middle column
$ret = "<div class='filemanager_main'>" . $this->getContent() . "</div>";
$cssLayout->setMiddleColumnContent($ret);

// Display the Layout
echo $cssLayout->show();
?>