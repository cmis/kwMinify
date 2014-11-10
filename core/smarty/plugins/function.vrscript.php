<?php
/**
 *    This file is part of OXID eShop Community Edition.
 *
 *    OXID eShop Community Edition is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    OXID eShop Community Edition is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with OXID eShop Community Edition.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      http://www.oxid-esales.com
 * @package   smarty_plugins
 * @copyright (C) OXID eSales AG 2003-2013
 * @version OXID eShop CE
 * @version   SVN: $Id: function.kwscript.php 52564 2012-11-29 07:36:27Z alfonsas $
 */

/**
 * Smarty plugin
 * -------------------------------------------------------------
 * File: function.kwscript.php
 * Type: string, html
 * Name: kwscript
 * Purpose: Collect given javascript includes/calls, but include/call them at the bottom of the page.
 *
 * Add [{kwscript add="oxid.popup.load();" }] to add script call.
 * Add [{kwscript source="oxid.js" }] to add script source.
 * Add [{kwscript include="oxid.js"}] to include local javascript file.
 * Add [{kwscript include="oxid.js?20120413"}] to include local javascript file with query string part.
 * Add [{kwscript include="http://www.oxid-esales.com/oxid.js"}] to include external javascript file.
 *
 * IMPORTANT!
 * Do not forget to add plain [{kwscript}] tag before closing body tag, to output all collected script includes and calls.
 * -------------------------------------------------------------
 *
 * @param array  $params  params
 * @param Smarty &$smarty clever simulation of a method
 *
 * @return string
 */
function smarty_function_kwscript($params, &$smarty)
{
	// $smarty->loadPlugin('oxscript');
	require_once($smarty->_get_plugin_filepath('function', 'oxscript'));

	$myConfig             = oxRegistry::getConfig();
	$sSufix               = ($smarty->_tpl_vars["__oxid_include_dynamic"])?'_dynamic':'';
	$sIncludes            = 'includes'.$sSufix;
	$sScripts             = 'scripts'.$sSufix;
	$iPriority            = ($params['priority'])?$params['priority']:3;
	$sWidget              = ($params['widget']?$params['widget']:'');
	$blInWidget           = ($params['inWidget']?$params['inWidget']:false);
	$aScript              = (array) $myConfig->getGlobalParameter($sScripts);
	$aInclude             = (array) $myConfig->getGlobalParameter($sIncludes);
	$sOutput              = '';
	$blMinify             = $myConfig->getConfigParam( 'kwUseMinify' );
	$blMinify             = ($blMinify==2)?$myConfig->isProductiveMode():$blMinify;


	if ( $params['add'] ) {
		$sScript = trim( $params['add'] );
		if ( !in_array($sScript, $aScript)) {
			$aScript[] = $sScript;
		}
		$myConfig->setGlobalParameter($sScripts, $aScript);

	} elseif ( $params['source'] ) {
		$sScript = $params['source'];
		if ($sSPath = $myConfig->getResourcePath($sScript, $myConfig->isAdmin())) {
			$sScript = file_get_contents($sSPath);
			$aScript[] = $sScript;
		} else {
			if ($myConfig->getConfigParam( 'iDebug' ) != 0) {
				$sError = "{kwscript} resource not found: ".htmlspecialchars($params['source']);
				trigger_error($sError, E_USER_WARNING);
			}
			return;
		}
		$myConfig->setGlobalParameter($sScripts, $aScript);

	} elseif ( $params['include'] ) {
		$sScript = $params['include'];
		if (!preg_match('#^(https?:)?//#', $sScript)) {
			$sOriginalScript = $sScript;

			// Separate query part #3305.
			$aScript = explode('?', $sScript);
			$sScript = $myConfig->getResourceUrl($aScript[0], $myConfig->isAdmin());

			if ($sScript && count($aScript) > 1) {
				// Append query part if still needed #3305.
				$sScript .= '?'.$aScript[1];
			} elseif ($sSPath = $myConfig->getResourcePath($sOriginalScript, $myConfig->isAdmin())) {
				// Append file modification timestamp #3725.
				//$sScript .= '?'.filemtime($sSPath);
				$sScript = $sScript;
			}
		}

		// File not found ?
		if (!$sScript) {
			if ($myConfig->getConfigParam( 'iDebug' ) != 0) {
				$sError = "{kwscript} resource not found: ".htmlspecialchars($params['include']);
				trigger_error($sError, E_USER_WARNING);
			}
			return;
		} else {
			$aInclude[$iPriority][] = $sScript;
			$aInclude[$iPriority]   = array_unique($aInclude[$iPriority]);
			$myConfig->setGlobalParameter($sIncludes, $aInclude);
		}
	} elseif ( $blMinify && !$sWidget && !$blInWidget ) {
		// minify not allowed for widgets, sWidget=='', blInWidget==false.

		// Render output for includes.
		$sOutput .= _kwscript_include( $aInclude, '' );
		$myConfig->setGlobalParameter( $sIncludes, null );

		// Render output for adds.
		$sScriptOutput = '';
		$sScriptOutput .= _oxscript_execute( $aScript, '', $sScripts );
		$myConfig->setGlobalParameter( $sScripts, null );
		$sOutput .= _oxscript_execute_enclose( $sScriptOutput, '' );

	} elseif ( !$sWidget || $blInWidget ) {
		// Form output for includes.
		$sOutput .= _oxscript_include( $aInclude, $sWidget );
		$myConfig->setGlobalParameter( $sIncludes, null );
		if ( $sWidget ) {
			$aIncludeDyn = (array) $myConfig->getGlobalParameter( $sIncludes .'_dynamic' );
			$sOutput .= _oxscript_include( $aIncludeDyn, $sWidget );
			$myConfig->setGlobalParameter( $sIncludes .'_dynamic', null );
		}

		// Form output for adds.
		$sScriptOutput = '';
		$sScriptOutput .= _oxscript_execute( $aScript, $sWidget, $sScripts );
		$myConfig->setGlobalParameter( $sScripts, null );
		if ( $sWidget ) {
			$aScriptDyn = (array) $myConfig->getGlobalParameter( $sScripts .'_dynamic' );
			$sScriptOutput .= _oxscript_execute( $aScriptDyn, $sWidget, $sScripts );
			$myConfig->setGlobalParameter( $sScripts .'_dynamic', null );
		}
		$sOutput .= _oxscript_execute_enclose( $sScriptOutput, $sWidget );
	}

	return $sOutput;
}

/**
 * Render output for includes with respect to minify settings.
 *
 * @param array  $aInclude string files to include.
 * @param string $sWidget  widget name.
 *
 * @return string
 */
function _kwscript_include( $aInclude, $sWidget )
{
	$myConfig    = oxRegistry::getConfig();
	$sOutput     = '';
	$sLoadOutput = '';

	if ( !count( $aInclude ) ) {
		return '';
	}

	// Sort by priority.
	ksort( $aInclude );
	$aUsedSrc = array();
	$aWidgets = array();
	$aMinifySrc = array();
	$sMinifyBase = preg_replace(array('@^'.$myConfig->getShopMainUrl().'@', '@/$@s'), '', $myConfig->getOutUrl(), 1);
	$sMinifyPrefix = $myConfig->getShopMainUrl() . 'min/b=' . $sMinifyBase . '&amp;f=';
	foreach ( $aInclude as $aPriority ) {
		foreach ( $aPriority as $sSrc ) {
			// Check for duplicated lower priority resources #3062.
			if ( !in_array( $sSrc, $aUsedSrc )) {
				// Minify only locals and no minifieds an no fonts and no packed -- TODO
				if ( strpos($sSrc, $myConfig->getOutUrl() ) === 0
						&& strpos($sSrc, '.min.js') === false
						&& strpos($sSrc, '.font.js') === false
						&& strpos($sSrc, '-packed.js') === false) {
					$aSrc = explode('?', $sSrc);
					$aMinifySrc[] = str_replace($myConfig->getOutUrl(), '', $aSrc[0]);
				} else {
					if ( count($aMinifySrc) ) {
						$sMinifySrc = $sMinifyPrefix . implode(',', $aMinifySrc);
						$sOutput .= '<script type="text/javascript" src="'.$sMinifySrc.'"></script>'.PHP_EOL;
						$aMinifySrc = array();
					}
					$sOutput .= '<script type="text/javascript" src="'.$sSrc.'"></script>'.PHP_EOL;
				}
				// Flush if max files reached, prevents too long URL and bad redirecting -- TODO
				if ( count($aMinifySrc) >= 5 ) {
					$sMinifySrc = $sMinifyPrefix . implode(',', $aMinifySrc);
					$sOutput .= '<script type="text/javascript" src="'.$sMinifySrc.'"></script>'.PHP_EOL;
					$aMinifySrc = array();
				}
			}
			$aUsedSrc[] = $sSrc;
		}
	}
	if (count($aMinifySrc)) {
		$sMinifySrc = $sMinifyPrefix . implode(',', $aMinifySrc);
		$sOutput .= '<script type="text/javascript" src="'.$sMinifySrc.'"></script>'.PHP_EOL;
	}

	return $sOutput;
}
