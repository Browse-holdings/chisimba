<?php

$this->loadClass('htmlheading', 'htmlelements');

$header = new htmlheading();
$header->type = 1;
$header->str = ucwords($title);

echo $header->show();


if ($this->getParam('message') != '' ) {
    switch ($this->getParam('message'))
    {
        default:
            $message = '';
            break;
        case 'contextupdated':
            $contextcode = $this->getParam('contextcode', Null);
            //Get Context Title
            $title = $this->objContext->getTitle($contextcode);
            if(empty($title))
                $title = $this->objContext->getMenuText($contextcode);
            $message = $title.' '.$this->objLanguage->languageText('mod_contextadmin_successfullyupdated', 'contextadmin', 'has been successfully updated').'!';
            break;
        case 'contextdeleted':
            $message = $this->getParam('title').' '.$this->objLanguage->languageText('mod_contextadmin_hasbeendeleted', 'contextadmin', 'has been successfully deleted');
            break;
    }
    
    if ($message != '' ) {
        
        $this->setSession('displayconfirmationmessage', FALSE);
        
        $timeoutMessage = $this->getObject('timeoutmessage', 'htmlelements');
        $timeoutMessage->setMessage($message);
        $timeoutMessage->timeout = 10000;
        
        echo '<p>'.$timeoutMessage->show().'</p>';
    }
}

if (isset($content)) {
    echo $content;
} else {
    $objDisplayContext = $this->getObject('displaycontext', 'context');
    
    foreach ($contexts as $context)
    {
        echo $objDisplayContext->formatContextDisplayBlock($context);
    }
}

?>
