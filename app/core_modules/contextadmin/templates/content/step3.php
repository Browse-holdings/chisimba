<?php

$this->loadClass('htmlheading', 'htmlelements');
$this->loadClass('form', 'htmlelements');
$this->loadClass('button', 'htmlelements');
$this->loadClass('hiddeninput', 'htmlelements');


$objIcon = $this->newObject('geticon', 'htmlelements');
$objIcon->setIcon('loader');

$formAction = 'savestep3';
$headerTitle = $context['title'].' - '.$this->objLanguage->code2Txt('mod_contextadmin_outcomes', 'contextadmin', NULL, '[-context-] Outcomes');
$formButton = $this->objLanguage->languageText('mod_contextadmin_gotonextstep', 'contextadmin', 'Go to Next Step');


$objStepMenu = $this->newObject('stepmenu', 'navigation');
if ($mode == 'edit') {
    $objStepMenu->addStep(str_replace('[-num-]', 1, $this->objLanguage->code2Txt('mod_contextadmin_stepnumber', 'contextadmin', NULL, 'Step [-num-]')).' - '.ucwords($this->objLanguage->code2Txt('mod_context_contextsettings', 'context', NULL, '[-context-] Settings')), ucwords($this->objLanguage->code2Txt('mod_contextadmin_updatecontextitlesettings', 'contextadmin', NULL, 'Update [-context-] Title and Settings')));
} else {
    $objStepMenu->addStep(str_replace('[-num-]', 1, $this->objLanguage->code2Txt('mod_contextadmin_stepnumber', 'contextadmin', NULL, 'Step [-num-]')).' - '.ucwords($this->objLanguage->code2Txt('mod_context_contextsettings', 'context', NULL, '[-context-] Settings')), $this->objLanguage->code2Txt('mod_contextadmin_checkcontextcodeavailable', 'contextadmin', NULL, 'Enter [-context-] settings and check whether [-context-] code is available'));
}
$objStepMenu->addStep(str_replace('[-num-]', 2, $this->objLanguage->code2Txt('mod_contextadmin_stepnumber', 'contextadmin', NULL, 'Step [-num-]')).' - '.ucwords($this->objLanguage->code2Txt('mod_contextadmin_contextinformation', 'contextadmin', NULL, '[-context-] Information')), $this->objLanguage->code2Txt('mod_contextadmin_enterinfoaboutcontext', 'contextadmin', NULL, 'Enter more information about your [-context-] and select a [-context-] image'));
$objStepMenu->addStep(str_replace('[-num-]', 3, $this->objLanguage->code2Txt('mod_contextadmin_stepnumber', 'contextadmin', NULL, 'Step [-num-]')).' - '.ucwords($this->objLanguage->code2Txt('mod_contextadmin_contextinformation', 'contextadmin', NULL, '[-context-] Information')), $this->objLanguage->code2Txt('mod_context_enteroutcomecontext', 'contextadmin', NULL, 'Enter the main Outcomes / Goals of the [-context-]'));
$objStepMenu->addStep(str_replace('[-num-]', 4, $this->objLanguage->code2Txt('mod_contextadmin_stepnumber', 'contextadmin', NULL, 'Step [-num-]')).' - '.ucwords($this->objLanguage->code2Txt('mod_context_contextpluginsabs', 'context', array('plugins'=>'plugins'), '[-context-] [-plugins-]')), $this->objLanguage->code2Txt('mod_contextadmin_selectpluginsforcontextabs', 'contextadmin', array('plugins'=>'plugins'), 'Select the [-plugins-] you would like to use in this [-context-]'));
$objStepMenu->setCurrent(3);
echo $objStepMenu->show();


$header = new htmlheading();
$header->type = 1;
$header->str = ucwords($headerTitle);

echo '<br />'.$header->show();




$objSelectImage = $this->getObject('selectimage', 'filemanager');
$htmlEditor = $this->newObject('htmlarea', 'htmlelements');
$htmlEditor->name = 'goals';
$htmlEditor->value = $context['goals'];

$table = $this->newObject('htmltable', 'htmlelements');
$table->startRow();

$table->addCell(Null);
$table->addCell('<p>'.$this->objLanguage->code2Txt('mod_context_enteroutcomecontext', 'contextadmin', NULL, 'Enter the Outcomes/Goals of the [-context-]').':</p>'.$htmlEditor->show());
$table->endRow();




$button = new button ('savecontext', $formButton);
$button->setToSubmit();



$form = new form ('createcontext', $this->uri(array('action'=>$formAction)));

$backUri = $this->uri(array('action'=>'edit','contextcode'=>$contextCode),'contextadmin');
$backButton = new button('back', $this->objLanguage->languageText('word_back'),"document.location='$backUri'");

$form->addToForm($table->show());
$form->addToForm($backButton->show()." ".$button->show());

$hiddenInput = new hiddeninput('mode', $mode);
$form->addToForm($hiddenInput->show());

$hiddenInput = new hiddeninput('contextCode', $contextCode);
$form->addToForm($hiddenInput->show());

echo $form->show();




?>
