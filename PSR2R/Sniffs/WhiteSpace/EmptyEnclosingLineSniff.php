<?php

namespace PSR2R\Sniffs\WhiteSpace;

class EmptyEnclosingLineSniff implements \PHP_CodeSniffer_Sniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return [
			T_CLASS,
			T_INTERFACE,
			T_TRAIT,
		];
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param \PHP_CodeSniffer_File $phpcsFile The file being scanned.
	 * @param int $stackPtr The position of the current token in the
	 *                                        stack passed in $tokens.
	 * @return void
	 */
	public function process(\PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();
		$errorData = [strtolower($tokens[$stackPtr]['content'])];

		if (isset($tokens[$stackPtr]['scope_opener']) === false) {
			$error = 'Possible parse error: %s missing opening or closing brace';
			$phpcsFile->addWarning($error, $stackPtr, 'MissingBrace', $errorData);
			return;
		}

		$curlyBraceStartIndex = $tokens[$stackPtr]['scope_opener'];
		$curlyBraceEndIndex = $tokens[$stackPtr]['scope_closer'];

		$lastContentIndex = $phpcsFile->findPrevious(T_WHITESPACE, ($curlyBraceEndIndex - 1), $stackPtr, true);

		if ($lastContentIndex === $curlyBraceStartIndex) {
			// Single new line for empty classes
			if ($tokens[$curlyBraceEndIndex]['line'] === $tokens[$curlyBraceStartIndex]['line'] + 1) {
				return;
			}

			$error = 'Closing brace of an empty %s must have a single new line between curly brackets';

			$fix = $phpcsFile->addFixableError($error, $curlyBraceEndIndex, 'CloseBraceNewLine', $errorData);
			if ($fix === true) {
				if ($curlyBraceEndIndex - $curlyBraceStartIndex === 1) {
					$phpcsFile->fixer->addNewline($curlyBraceStartIndex);
				} else {
					$phpcsFile->fixer->replaceToken($curlyBraceStartIndex + 1, '');
					$phpcsFile->fixer->addNewline($curlyBraceStartIndex + 1);
				}
			}
			return;
		}

		$contentLine = $tokens[$lastContentIndex]['line'];
		$braceLine = $tokens[$curlyBraceEndIndex]['line'];

		if ($braceLine !== $contentLine + 2) {
			$phpcsFile->recordMetric($stackPtr, 'Class closing brace placement', 'lines');
			$error = 'Closing brace of a %s must have a new line between itself and the last content.';

			$fix = $phpcsFile->addFixableError($error, $curlyBraceEndIndex, 'CloseBraceNewLine', $errorData);
			if ($fix === true) {
				if ($braceLine < $contentLine + 2) {
					$phpcsFile->fixer->addNewlineBefore($curlyBraceEndIndex);
				} else {
					$phpcsFile->fixer->replaceToken($curlyBraceEndIndex - 1, '');
				}
			}
		}
	}

}
