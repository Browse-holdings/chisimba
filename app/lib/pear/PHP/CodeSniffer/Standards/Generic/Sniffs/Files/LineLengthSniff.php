<?php
/**
 * Generic_Sniffs_Files_LineLengthSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

require_once 'PHP/CodeSniffer/Sniff.php';

/**
 * Generic_Sniffs_Files_LineLengthSniff.
 *
 * Checks all lines in the file, and throws warnings if they are over 80
 * characters in length.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   Release: 0.6.0
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Generic_Sniffs_Files_LineLengthSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * The limit that the length of a line should not exceed.
     *
     * @var int
     */
    protected $lineLimit = 80;


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_OPEN_TAG);

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Make sure this is the first open tag.
        $previousOpenTag = $phpcsFile->findPrevious(array(T_OPEN_TAG), ($stackPtr - 1));
        if ($previousOpenTag !== false) {
            return;
        }

        $tokenLimit         = count($tokens);
        $tokenCount         = 0;
        $currentLineContent = '';
        $currentLine        = 1;
        $longLines          = array();

        for (; $tokenCount < $tokenLimit; $tokenCount++) {
            if ($tokens[$tokenCount]['line'] === $currentLine) {
                $currentLineContent .= $tokens[$tokenCount]['content'];
            } else {
                $currentLineContent = trim($currentLineContent, "\n");
                $lineLength         = strlen($currentLineContent);

                if ($lineLength > $this->lineLimit) {
                    $longLines[] = ($tokenCount - 1);
                }

                $currentLineContent = $tokens[$tokenCount]['content'];
                $currentLine++;
            }
        }

        foreach ($longLines as $lineToken) {
            $warning = 'Line exceeds '.$this->lineLimit.' characters';
            $phpcsFile->addWarning($warning, $lineToken);
        }

    }//end process()


}//end class

?>
