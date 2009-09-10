<?php

$display = '<h1>'.$this->objLanguage->languageText('mod_blog_searchresults', 'blog', 'Search Results').'</h1>';

$this->loadClass('textinput', 'htmlelements');
$this->loadClass('button', 'htmlelements');
$this->loadClass('form', 'htmlelements');
$this->loadClass('label', 'htmlelements');
$this->loadClass('hiddeninput', 'htmlelements');

$form = new form ('search', $this->uri(NULL));
$form->method = 'GET';

$formModule = new hiddeninput ('module', 'search');


$search = new textinput('search', $this->getParam('search'));
$module = new textinput('searchmodule', $this->getParam('searchmodule'));

$searchLabel = new label ($this->objLanguage->languageText('word_search', 'system', 'Search').': ', 'input_search');
$moduleLabel = new label ($this->objLanguage->languageText('word_module', 'system', 'Module').': ', 'input_module');

$form->addToForm($formModule->show().$searchLabel->show().$search->show());

$form->addToForm(' &nbsp; &nbsp; ');

$form->addToForm($moduleLabel->show().$module->show());

$button = new button ('go', $this->objLanguage->languageText('word_go', 'system', 'Go'));
$button->setToSubmit();

$form->addToForm(' &nbsp; '.$button->show());

$display .= $form->show();

$objSearchResults = $this->getObject('searchresults');

$searchKey = $this->getParam('search', $this->getParam('query'));

$display .= $objSearchResults->displaySearchResults($searchKey, $this->getParam('searchmodule'));

$this->setVar('middleContent', $display);

$cssLayout = $this->newObject('csslayout', 'htmlelements');
$cssLayout->setNumColumns(1);
$middleColumn = $display;

$cssLayout->setMiddleColumnContent($middleColumn);

echo $cssLayout->show();
?>
