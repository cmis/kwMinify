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
 * @version   SVN: $Id: function.kwstyle.php 28124 2010-06-03 11:27:00Z alfonsas $
 */

/**
 * Smarty plugin
 * -------------------------------------------------------------
 * File: function.kwstyle.php
 * Type: string, html
 * Name: kwstyle
 * Purpose: Collect given css files. but include them only at the top of the page.
 *
 * Add [{kwstyle include="oxid.css"}] to include local css file.
 * Add [{kwstyle include="oxid.css?20120413"}] to include local css file with query string part.
 * Add [{kwstyle include="http://www.oxid-esales.com/oxid.css"}] to include external css file.
 * Add [{kwstyle include="//www.oxid-esales.com/oxid.css"}] to include external css file, http(s) aware.
 *
 * IMPORTANT!
 * Do not forget to add plain [{kwstyle}] tag where you need to output all collected css includes.
 * -------------------------------------------------------------
 *
 * @param array  $params  params
 * @param Smarty &$smarty clever simulation of a method
 *
 * @return string
 */
function smarty_function_kwstyle($params, &$smarty)
{
	$myConfig   = oxRegistry::getConfig();
	$sSufix     = ($smarty->_tpl_vars["__oxid_include_dynamic"])?'_dynamic':'';
	$sWidget    = ($params['widget']?$params['widget']:'');
	$blInWidget = ($params['inWidget']?$params['inWidget']:false);
	$blMinify   = $myConfig->getConfigParam( 'kwUseMinify' );
	$blMinify   = ($blMinify==2)?$myConfig->isProductiveMode():$blMinify;

	$sCtyles  = 'conditional_styles'.$sSufix;
	$sStyles  = 'styles'.$sSufix;

	$aCtyles  = (array) $myConfig->getGlobalParameter($sCtyles);
	$aStyles  = (array) $myConfig->getGlobalParameter($sStyles);


	if ( $sWidget && !$blInWidget ) {
		return;
	}

	$sOutput  = '';
	if ( $params['include'] ) {
		$sStyle = $params['include'];
		if (!preg_match('#^(https?:)?//#', $sStyle)) {
			$sOriginalStyle = $sStyle;

			// Separate query part #3305.
			$aStyle = explode('?', $sStyle);
			$sStyle = $aStyle[0] = $myConfig->getResourceUrl($aStyle[0], $myConfig->isAdmin());

			if ($sStyle && count($aStyle) > 1) {
				// Append query part if still needed #3305.
				$sStyle .= '?'.$aStyle[1];
			} elseif ($sSPath = $myConfig->getResourcePath($sOriginalStyle, $myConfig->isAdmin())) {
				// Append file modification timestamp #3725.
				//$sStyle .= '?'.filemtime($sSPath);
				$sStyle = $sStyle;
			}
		}

		// File not found ?
		if (!$sStyle) {
			if ($myConfig->getConfigParam( 'iDebug' ) != 0) {
				$sError = "{kwstyle} resource not found: ".htmlspecialchars($params['include']);
				trigger_error($sError, E_USER_WARNING);
			}
			return;
		}

		// Conditional comment ?
		if ($params['if']) {
			$aCtyles[$sStyle] = $params['if'];
			$myConfig->setGlobalParameter($sCtyles, $aCtyles);
		} else {
			$aStyles[] = $sStyle;
			$aStyles = array_unique($aStyles);
			$myConfig->setGlobalParameter($sStyles, $aStyles);
		}
	} else {
		if ($blMinify) {
			$aMinifySrc = array();
			$sMinifyBase = preg_replace(array('@^'.$myConfig->getShopMainUrl().'@', '@/$@s'), '', $myConfig->getOutUrl());
			foreach ($aStyles as $sSrc) {
				if (strpos($sSrc, $myConfig->getOutUrl()) === 0) {
					$aSrc = explode('?', $sSrc);
					$aMinifySrc[] = str_replace($myConfig->getOutUrl(), '', $aSrc[0]);
				} else {
					// remotes
					$sOutput .= '<link rel="stylesheet" type="text/css" href="'.$sSrc.'" />'.PHP_EOL;
				}
			}
			if(count($aMinifySrc)) {
				$sMinifySrc = $myConfig->getShopMainUrl() . 'min/b=' . $sMinifyBase . '&amp;f=' . implode(',', $aMinifySrc);
				$sOutput .= '<link rel="stylesheet" type="text/css" href="'.$sMinifySrc.'" />'.PHP_EOL;
			}
		} else {
			foreach ($aStyles as $sSrc) {
				$sOutput .= '<link rel="stylesheet" type="text/css" href="'.$sSrc.'" />'.PHP_EOL;
			}
		}
		foreach ($aCtyles as $sSrc => $sCondition) {
			$sOutput .= '<!--[if '.$sCondition.']><link rel="stylesheet" type="text/css" href="'.$sSrc.'"><![endif]-->'.PHP_EOL;
		}
	}

	return $sOutput;
}
