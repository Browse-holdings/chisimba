<?php
/**
 * Generic_Sniffs_Formatting_MultipleStatementAlignmentSniff.
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
require_once 'PHP/CodeSniffer/Tokens.php';

/**
 * Generic_Sniffs_Formatting_MultipleStatementAlignmentSniff.
 *
 * Checks alignment of assignments. If there are multiple adjacent assignments,
 * it will check that the equals signs of each assignment are aligned. It will
 * display a warning to advise that the signs should be aligned.
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
class Generic_Sniffs_Formatting_MultipleStatementAlignmentSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * If true, an error will be thrown; otherwise a warning.
     *
     * @var bool
     */
    protected $error = false;


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return PHP_CodeSniffer_Tokens::$assignmentTokens;

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

        $assignedVariable = $phpcsFile->findPrevious(array(T_VARIABLE, T_CONST), ($stackPtr - 1));
        if ($assignedVariable === false) {
            return;
        }

        if ($tokens[$assignedVariable]['code'] === T_VARIABLE) {
            if ($this->_isAssignment($phpcsFile, $assignedVariable) === false) {
                return;
            }
        }

        /*
            By this stage, it is known that there is an assignment on this line.
            We only want to process the block once we reach the last assignment,
            so we need to determine if there are more to follow.
        */

        if (($nextAssign = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$assignmentTokens, ($stackPtr + 1))) !== false) {
            if ($tokens[$nextAssign]['line'] === ($tokens[$stackPtr]['line'] + 1)) {
                return;
            }
        }

        // OK, getting here means that this is the last in a block of statements.
        $assignments         = array();
        $assignments[]       = $stackPtr;
        $prevAssignment      = $stackPtr;
        $lastLine            = $tokens[$stackPtr]['line'];
        $maxVariableLength   = 0;
        $maxAssignmentLength = 0;

        while (($prevAssignment = $phpcsFile->findPrevious(PHP_CodeSniffer_Tokens::$assignmentTokens, ($prevAssignment - 1))) !== false) {
            if ($tokens[$prevAssignment]['line'] !== ($lastLine - 1)) {
                break;
            }

            $checkingVariable = $phpcsFile->findPrevious(array(T_VARIABLE, T_CONST), ($prevAssignment - 1));
            if ($checkingVariable === false) {
                break;
            }

            if ($tokens[$checkingVariable]['line'] !== $tokens[$prevAssignment]['line']) {
                break;
            }

            if ($tokens[$checkingVariable]['code'] === T_VARIABLE) {
                if ($this->_isAssignment($phpcsFile, $checkingVariable) === false) {
                    break;
                }
            }

            $assignments[] = $prevAssignment;
            $lastLine--;
        }//end while

        $assignmentData = array();
        foreach ($assignments as $assignment) {
            $variable = $phpcsFile->findPrevious(array(T_VARIABLE, T_CONST), $assignment);
            if ($tokens[$variable]['code'] === T_VARIABLE) {
                // Check that this variable is having a value assigned to it.
                // We don't check constants because they are not ambiguous.
                if ($this->_isAssignment($phpcsFile, $variable) === false) {
                    break;
                }
            }

            $variableContent = $phpcsFile->getTokensAsString($variable, ($assignment - $variable));
            $variableContent = rtrim($variableContent);
            $contentLength   = strlen($variableContent);

            if ($maxVariableLength < ($contentLength + $tokens[$variable]['column'])) {
                $maxVariableLength = ($contentLength + $tokens[$variable]['column']);
            }

            if ($maxAssignmentLength < strlen($tokens[$assignment]['content'])) {
                $maxAssignmentLength = strlen($tokens[$assignment]['content']);
            }

            $assignmentData[$assignment] = array(
                                            'variable_length'   => $contentLength + $tokens[$variable]['column'],
                                            'assignment_length' => strlen($tokens[$assignment]['content']),
                                           );
        }//end foreach

        foreach ($assignmentData as $assignment => $data) {
            if ($data['assignment_length'] === $maxAssignmentLength) {
                if ($data['variable_length'] === $maxVariableLength) {
                    // The assignment is the longest possible, so the column that
                    // everything has to align to is based on it.
                    $column = ($maxVariableLength + 1);
                    break;
                } else {
                    // The assignment token is the longest out of all of the
                    // assignments, but the variable name is not, so the column
                    // the start at can go back more to cover the space
                    // between the variable name and the assigment operator.
                    $column = ($maxVariableLength - ($maxAssignmentLength - 1) + 1);
                }
            }
        }

        // Determine the actual position that each equals sign should be in.
        foreach ($assignments as $assignment) {
            // Actual column takes into account the length of the assignment operator.
            $actualColumn = ($column + $maxAssignmentLength - strlen($tokens[$assignment]['content']));
            if ($tokens[$assignment]['column'] !== $actualColumn) {
                $variable        = $phpcsFile->findPrevious(array(T_VARIABLE, T_CONST), $assignment);
                $variableContent = $phpcsFile->getTokensAsString($variable, ($assignment - $variable));
                $variableContent = rtrim($variableContent);
                $contentLength   = strlen($variableContent);

                $leadingSpace = $tokens[$variable]['column'];
                $expected     = ($actualColumn - ($contentLength + $leadingSpace));
                $expected    .= ($expected === 1) ? ' space' : ' spaces';
                $found        = ($tokens[$assignment]['column'] - ($contentLength + $leadingSpace));
                $found       .= ($found === 1) ? ' space' : ' spaces';

                if (count($assignments) === 1) {
                    $error = "Equals sign not aligned correctly; expected $expected but found $found";
                } else {
                    $error = "Equals sign not aligned with surrounding assignments; expected $expected but found $found";
                }

                if ($this->error === true) {
                    $phpcsFile->addError($error, $assignment);
                } else {
                    $phpcsFile->addWarning($error, $assignment);
                }
            }//end if
        }//end foreach

    }//end process()


    /**
     * Determines if the stackPtr is an assignment or not.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file where to token exists.
     * @param int                  $stackPtr  The position in the stack to check.
     *
     * @return boolean
     */
    private function _isAssignment(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        if ($stackPtr === false) {
            return false;
        }

        $tokens = $phpcsFile->getTokens();

        // Look for the first token on the line. If it is a variable and within
        // the same statement, then that is the real variable we are checking.
        // This gets around problems with code like this:
        // $array[($blah + (10 - $test))] = 'anything';
        $varLine = $tokens[$stackPtr]['line'];
        for ($stackPtr = ($stackPtr - 1); $stackPtr > 0; $stackPtr--) {
            if ($tokens[$stackPtr]['line'] !== $varLine) {
                $stackPtr++;
                break;
            }

            if ($tokens[$stackPtr]['content'] === ';') {
                $stackPtr++;
                break;
            }
        }

        $stackPtr = $phpcsFile->findNext(T_WHITESPACE, $stackPtr, null, true);
        if ($tokens[$stackPtr]['code'] !== T_VARIABLE) {
            return false;
        }

        // If this variable is followed by an object operator, we need to skip
        // the member var component.
        $nextContent = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if ($tokens[$nextContent]['code'] === T_OBJECT_OPERATOR) {
            $stackPtr = $phpcsFile->findNext(T_WHITESPACE, ($nextContent + 1), null, true);
        }

        return true;

    }//end _isAssignment()


}//end class

?>
