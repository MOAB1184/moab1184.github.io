<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

namespace MediaWiki\Title;

use Language;

/**
 * A parser that translates page titles on a foreign wiki into ForeignTitle
 * objects, with no knowledge of the namespace setup on the foreign site.
 */
class NaiveForeignTitleFactory implements ForeignTitleFactory {

	/** @var Language */
	private $contentLanguage;

	/**
	 * @param Language $contentLanguage
	 */
	public function __construct( Language $contentLanguage ) {
		$this->contentLanguage = $contentLanguage;
	}

	/**
	 * Create a ForeignTitle object.
	 *
	 * Based on the page title and optionally the namespace ID, of a page on a foreign wiki.
	 * These values could be, for example, the `<title>` and `<ns>` attributes found in an
	 * XML dump.
	 *
	 * Although exported XML dumps have contained a map of namespace IDs to names
	 * since MW 1.5, the importer used to completely ignore the `<siteinfo>` tag
	 * before MW 1.25.  It is therefore possible that custom XML dumps (i.e. not
	 * generated by Special:Export) have been created without this metadata.
	 * As a result, this code falls back to using namespace data for the local
	 * wiki (similar to buggy pre-1.25 behaviour) if $ns is not supplied.
	 *
	 * @param string $title The page title
	 * @param int|null $ns The namespace ID, or null if this data is not available
	 * @return ForeignTitle
	 */
	public function createForeignTitle( $title, $ns = null ) {
		$pieces = explode( ':', $title, 2 );

		/**
		 * Can we assume that the part of the page title before the colon is a
		 * namespace name?
		 *
		 * XML export schema version 0.5 and earlier (MW 1.18 and earlier) does not
		 * contain a <ns> tag, so we need to be able to handle that case.
		 *
		 * If we know the namespace ID, we assume a non-zero namespace ID means
		 * the ':' sets off a valid namespace name. If we don't know the namespace
		 * ID, we fall back to using the local wiki's namespace names to resolve
		 * this -- better than nothing, and mimics the old crappy behavior
		 */
		$isNamespacePartValid = $ns === null
			? $this->contentLanguage->getNsIndex( $pieces[0] ) !== false
			: $ns != 0;

		if ( count( $pieces ) === 2 && $isNamespacePartValid ) {
			[ $namespaceName, $pageName ] = $pieces;
		} else {
			$namespaceName = '';
			$pageName = $title;
		}

		return new ForeignTitle( $ns, $namespaceName, $pageName );
	}
}

/**
 * Retain the old class name for backwards compatibility.
 * @deprecated since 1.41
 */
class_alias( NaiveForeignTitleFactory::class, 'NaiveForeignTitleFactory' );
