<?php

/**
 * ownCloud search lucene
 *
 * @author Jörn Dreyer
 * @copyright 2014 Jörn Friedrich Dreyer jfd@butonic.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Search_Lucene\Tests\Unit;


//add 3rdparty folder to include path
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__.'/../../3rdparty');

//initialize zend autoloader
require_once __DIR__ . '/../../3rdparty/Zend/Loader/Autoloader.php';
\Zend_Loader_Autoloader::getInstance();

class DummyIndex implements \Zend_Search_Lucene_Interface {
	var $documents = array();
	public function addDocument(\Zend_Search_Lucene_Document $document) {
		$this->documents[] = $document;
	}
	public function getDocument($id) {
		return $this->documents[$id];
	}
	public function addReference() {}
	public function closeTermsStream() {}
	public function commit() {}
	public function count() {}
	public function currentTerm() {}
	public function delete($id) {}
	public function docFreq(\Zend_Search_Lucene_Index_Term $term) {}
	public function find($query) {}
	public function getDirectory() {}
	public function getFieldNames($indexed = false) {}
	public function getFormatVersion() {}
	public function getMaxBufferedDocs() {}
	public function getMaxMergeDocs() {}
	public function getMergeFactor() {}
	public function getSimilarity() {}
	public function hasDeletions() {}
	public function hasTerm(\Zend_Search_Lucene_Index_Term $term) {}
	public function isDeleted($id) {}
	public function maxDoc() {}
	public function nextTerm() {}
	public function norm($id, $fieldName) {}
	public function numDocs() {}
	public function optimize() {}
	public function removeReference() {}
	public function resetTermsStream() {}
	public function setFormatVersion($formatVersion) {}
	public function setMaxBufferedDocs($maxBufferedDocs) {}
	public function setMaxMergeDocs($maxMergeDocs) {}
	public function setMergeFactor($mergeFactor) {}
	public function skipTo(\Zend_Search_Lucene_Index_Term $prefix) {}
	public function termDocs(\Zend_Search_Lucene_Index_Term $term, $docsFilter = null) {}
	public function termDocsFilter(\Zend_Search_Lucene_Index_Term $term, $docsFilter = null) {}
	public function termFreqs(\Zend_Search_Lucene_Index_Term $term, $docsFilter = null) {}
	public function termPositions(\Zend_Search_Lucene_Index_Term $term, $docsFilter = null) {}
	public function terms() {}
	public function undeleteAll() {}
	public static function getActualGeneration(\Zend_Search_Lucene_Storage_Directory $directory) {}
	public static function getDefaultSearchField() {}
	public static function getResultSetLimit() {}
	public static function getSegmentFileName($generation) {}
	public static function setDefaultSearchField($fieldName) {}
	public static function setResultSetLimit($limit) {}
}

class TestSearchProvider extends TestCase {

	/**
	 * @dataProvider searchResultDataProvider
	 */
	function testSearchLuceneResultContent(\Zend_Search_Lucene_Search_QueryHit $hit, $fileId, $name, $path, $size, $score, $mimeType, $modified, $container) {

		$searchResult = new \OCA\Search_Lucene\Search\LuceneResult($hit);

		$this->assertInstanceOf('OCA\Search_Lucene\Search\LuceneResult', $searchResult);
		$this->assertEquals($fileId, $searchResult->id);
		$this->assertEquals('lucene', $searchResult->type);
		$this->assertEquals($path, $searchResult->path);
		$this->assertEquals($name, $searchResult->name);
		$this->assertEquals($mimeType, $searchResult->mime_type);
		$this->assertEquals($size, $searchResult->size);
		$this->assertEquals($score, $searchResult->score);
		$this->assertEquals($modified, $searchResult->modified);
	}

	public function searchResultDataProvider() {

		$index = new DummyIndex();

		$doc1 = new \Zend_Search_Lucene_Document();
		$doc1->addField(\Zend_Search_Lucene_Field::Keyword('fileid', 10));
		$doc1->addField(\Zend_Search_Lucene_Field::Text('path', '/test/files/documents/document.txt', 'UTF-8'));
		$doc1->addField(\Zend_Search_Lucene_Field::unIndexed('mtime', 1234567));
		$doc1->addField(\Zend_Search_Lucene_Field::unIndexed('size', 123));
		$doc1->addField(\Zend_Search_Lucene_Field::unIndexed('mimetype', 'text/plain'));
		$index->addDocument($doc1);

		$doc2 = new \Zend_Search_Lucene_Document();
		$doc2->addField(\Zend_Search_Lucene_Field::Keyword('fileid', 20));
		$doc2->addField(\Zend_Search_Lucene_Field::Text('path', '/test/files/documents/document.pdf', 'UTF-8'));
		$doc2->addField(\Zend_Search_Lucene_Field::unIndexed('mtime', 1234567));
		$doc2->addField(\Zend_Search_Lucene_Field::unIndexed('size', 1234));
		$doc2->addField(\Zend_Search_Lucene_Field::unIndexed('mimetype', 'application/pdf'));
		$index->addDocument($doc2);

		$doc3 = new \Zend_Search_Lucene_Document();
		$doc3->addField(\Zend_Search_Lucene_Field::Keyword('fileid', 30));
		$doc3->addField(\Zend_Search_Lucene_Field::Text('path', '/test/files/documents/document.mp3', 'UTF-8'));
		$doc3->addField(\Zend_Search_Lucene_Field::unIndexed('mtime', 1234567));
		$doc3->addField(\Zend_Search_Lucene_Field::unIndexed('size', 12341234));
		$doc3->addField(\Zend_Search_Lucene_Field::unIndexed('mimetype', 'audio/mp3'));
		$index->addDocument($doc3);

		$doc4 = new \Zend_Search_Lucene_Document();
		$doc4->addField(\Zend_Search_Lucene_Field::Keyword('fileid', 40));
		$doc4->addField(\Zend_Search_Lucene_Field::Text('path', '/test/files/documents/document.jpg', 'UTF-8'));
		$doc4->addField(\Zend_Search_Lucene_Field::unIndexed('mtime', 1234567));
		$doc4->addField(\Zend_Search_Lucene_Field::unIndexed('size', 1234123));
		$doc4->addField(\Zend_Search_Lucene_Field::unIndexed('mimetype', 'image/jpg'));
		$index->addDocument($doc4);

		$hit1 = new \Zend_Search_Lucene_Search_QueryHit($index);
		$hit1->score = 0.4;
		$hit1->id = 0;

		$hit2 = new \Zend_Search_Lucene_Search_QueryHit($index);
		$hit2->score = 0.31;
		$hit2->id = 1;

		$hit3 = new \Zend_Search_Lucene_Search_QueryHit($index);
		$hit3->score = 0.299;
		$hit3->id = 2;

		$hit4 = new \Zend_Search_Lucene_Search_QueryHit($index);
		$hit4->score = 0.001;
		$hit4->id = 3;

		return array(
			// hit, name, size, score, mime_type, container
			array($hit1, '10', 'document.txt', '/documents/document.txt', 123, 0.4, 'text/plain', 1234567, 'documents'),
			array($hit2, '20', 'document.pdf', '/documents/document.pdf', 1234, 0.31, 'application/pdf', 1234567, 'documents'),
			array($hit3, '30', 'document.mp3', '/documents/document.mp3', 12341234, 0.299, 'audio/mp3', 1234567, 'documents'),
			array($hit4, '40', 'document.jpg', '/documents/document.jpg', 1234123, 0.001, 'image/jpg', 1234567, 'documents'),
		);
	}
}
