<?php

namespace OCA\Search_Lucene;

use \OC\Files\Filesystem;
use OCA\Search_Lucene\Document\Ods;
use OCA\Search_Lucene\Document\Odt;
use OCA\Search_Lucene\Document\Pdf;
use \OCP\Util;

/**
 * @author Jörn Dreyer <jfd@butonic.de>
 */
class Indexer {

	/**
	 * classname which used for hooks handling
	 * used as signalclass in OC_Hooks::emit()
	 */
	const CLASSNAME = 'Indexer';
	
	private $view;
	private $lucene;

	public function __construct(\OC\Files\View $view, Lucene $lucene) {
			$this->view = $view;
			$this->lucene = $lucene;
	}
	
	public function indexFiles (array $fileIds, \OC_EventSource $eventSource = null) {
		
		$skippedDirs = explode(';', \OCP\Config::getUserValue(\OCP\User::getUser(), 'search_lucene', 'skipped_dirs', '.git;.svn;.CVS;.bzr'));
		
		foreach ($fileIds as $id) {
			$skip = false;

			$fileStatus = \OCA\Search_Lucene\Status::fromFileId($id);

			try{
				// before we start mark the file as error so we know there
				// was a problem in case the php execution dies and we don't try
				// the file again
				$fileStatus->markError();

				$path = \OC\Files\Filesystem::getPath($id);

				if (empty($path)) {
					$skip = true;
				} else {
					foreach ($skippedDirs as $skippedDir) {
						if (strpos($path, '/' . $skippedDir . '/') !== false //contains dir
							|| strrpos($path, '/' . $skippedDir) === strlen($path) - (strlen($skippedDir) + 1) // ends with dir
						) {
							$skip = true;
							break;
						}
					}
				}
				
				if ($skip) {
					$fileStatus->markSkipped();
					\OCP\Util::writeLog('search_lucene',
						'skipping file '.$id.':'.$path,
						\OCP\Util::DEBUG);
					continue;
				}
				if ($eventSource) {
					$eventSource->send('indexing', $path);
				}
				
				if ($this->indexFile($path)) {
					$fileStatus->markIndexed();
				} else {
					\OCP\JSON::error(array('message' => 'Could not index file '.$id.':'.$path));
					if ($eventSource) {
						$eventSource->send('error', $path);
					}
				}
			} catch (\Exception $e) { //sqlite might report database locked errors when stock filescan is in progress
				//this also catches db locked exception that might come up when using sqlite
				\OCP\Util::writeLog('search_lucene',
					$e->getMessage() . ' Trace:\n' . $e->getTraceAsString(),
					\OCP\Util::ERROR);
				\OCP\JSON::error(array('message' => 'Could not index file.'));
					if ($eventSource) {
						$eventSource->send('error', $e->getMessage());
					}
				//try to mark the file as new to let it reindex
				$fileStatus->markNew();  // Add UI to trigger rescan of files with status 'E'rror?
			}
		}
	}

	/**
	 * index a file
	 *
	 * @author Jörn Dreyer <jfd@butonic.de>
	 *
	 * @param string $path the path of the file
	 *
	 * @return bool
	 */
	public function indexFile($path = '') {

		if (!Filesystem::isValidPath($path)) {
			return;
		}
		if (empty($path)) {
			//ignore the empty path element
			return false;
		}
		
		if(!$this->view->file_exists($path)) {
			Util::writeLog('search_lucene',
				'file '.$path.' vanished, ignoring',
				Util::DEBUG);
			return true;
		}

		// the cache already knows mime and other basic stuff
		/** @var \OC\Files\FileInfo $data */
		$data = $this->view->getFileInfo($path);

		if (isset($data)) {

			// we decide how to index on mime type or file extension
			$mimeType = $data->getMimetype();
			$fileExtension = strtolower(pathinfo($data->getName(), PATHINFO_EXTENSION));

			// initialize plain lucene document
			$doc = new \Zend_Search_Lucene_Document();

			// index content for local files only
			$localFile = $this->view->getLocalFile($path);

			if ( $localFile ) {
				//try to use special lucene document types

				if ('text/plain' === $data->getMimetype()) {

					$body = $this->view->file_get_contents($path);

					if ($body != '') {
						$doc->addField(\Zend_Search_Lucene_Field::UnStored('body', $body));
					}

				// FIXME other text files? c, php, java ...

				} else if ('text/html' === $mimeType) {

					//TODO could be indexed, even if not local
					$doc = \Zend_Search_Lucene_Document_Html::loadHTML($this->view->file_get_contents($path));

				} else if ('application/pdf' === $mimeType) {

					$doc = Pdf::loadPdf($this->view->file_get_contents($path));

				// the zend classes only understand docx and not doc files
				} else if ($fileExtension === 'docx') {

					$doc = \Zend_Search_Lucene_Document_Docx::loadDocxFile($localFile);

				//} else if ('application/msexcel' === $mimeType) {
				} else if ($fileExtension === 'xlsx') {

					$doc = \Zend_Search_Lucene_Document_Xlsx::loadXlsxFile($localFile);

				//} else if ('application/mspowerpoint' === $mimeType) {
				} else if ($fileExtension === 'pptx') {

					$doc = \Zend_Search_Lucene_Document_Pptx::loadPptxFile($localFile);

				} else if ($fileExtension === 'odt') {

					$doc = Odt::loadOdtFile($localFile);

				} else if ($fileExtension === 'ods') {

					$doc = Ods::loadOdsFile($localFile);

				}
			}

			// Store filecache id as unique id to lookup by when deleting
			$doc->addField(\Zend_Search_Lucene_Field::Keyword('fileid', $data->getId()));

			// Store document path for the search results
			$doc->addField(\Zend_Search_Lucene_Field::Text('path', $path, 'UTF-8'));

			$doc->addField(\Zend_Search_Lucene_Field::unIndexed('mtime', $data->getMTime()));

			$doc->addField(\Zend_Search_Lucene_Field::unIndexed('size', $data->getSize()));

			$doc->addField(\Zend_Search_Lucene_Field::unIndexed('mimetype', $mimeType));

			$this->lucene->updateFile($doc, $data->getId());

			return true;

		} else {
			Util::writeLog(
				'search_lucene',
				'need file info object for content extraction',
				Util::ERROR
			);
			return false;
		}
	}

}
