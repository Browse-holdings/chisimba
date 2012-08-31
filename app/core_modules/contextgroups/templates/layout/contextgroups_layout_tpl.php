<?php

/**
 * @package contextgroups
 */
/**
 * Layout template for the contextgroups module
 */
$cssLayout = $this->newObject('csslayout', 'htmlelements');
$leftMenu = $this->newObject('contextsidebar', 'context');


$cssLayout->setLeftColumnContent($leftMenu->show());
$cssLayout->setMiddleColumnContent($this->getContent());

$objModule = $this->getObject('modules', 'modulecatalogue');

echo $cssLayout->show();
?>