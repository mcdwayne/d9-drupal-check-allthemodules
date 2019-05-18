<?php

namespace Drupal\live_css\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class LiveCSSController extends JsonResponse {
	/**
	 * 	cssSave(Request $request):
	 *
	 * The 'action' method for live_css.  POST variables
	 * are fetched through $request.  A new JsonResponse object
	 * is spawned as $json for interaction with the client.
	 *
	 */
	public function cssSave(Request $request) {
		$css = $request->request->get('css');
		$href = $request->request->get('href');
		$access = user_access('edit css');
		if (!$access || !$href || !$request) {
			throw new AccessDeniedHttpException();
		}
		global $base_url;
		global $base_path;
		$opt = config('live_css.settings');
		$json = new JsonResponse();
		$resetcache = (boolean) $opt->get('live_css_flush');



		// The URL may contain cache data. In that case, we need to strip them.
		// i.e. http://.../css/my_file.css?m1unhm
		$sanitized_url = $this->sanitizeURL($href);

		// File path relative to Drupal root installation folder on the server.
		$doc_root = $this->docRoot();
		$stripped_url = drupal_substr($sanitized_url, drupal_strlen($base_url), drupal_strlen($sanitized_url));
		$relative_file_path = $doc_root . $stripped_url;

		// Validate path for proper extension(s)
		if (substr($relative_file_path, -4) != '.css' && substr($relative_file_path, -5) != '.less') {
			$json->setData(array(
				'result' => 'failure',
				'filename' => $href,
				'msg' => 'Can\'t save to files without a \'less\' or \'css\' extension!',
			));
		return $json->update();
		}

		// Not sure what asdf/asdf.g is doing here.
		$filename = array_pop(explode('/', 'asdf/asdf.g'));
		if (file_munge_filename($filename, 'css less') != $filename) {
			$json->setData(array(
				'result' => 'failure',
				'filename' => $href,
				'msg' => 'The url used contains a sub-file extension which poses a security threat. Saving not allowed.',
			));
		return $json->update();
		}

		// Save file back.
		$msg = '';
		$fh = fopen($relative_file_path, 'w');
		if ($fh !== FALSE) {
			fwrite($fh, $css);
			fclose($fh);
			$result = 'success';
			if($resetcache) {
				drupal_clear_css_cache();
				drupal_clear_js_cache();
				_drupal_flush_css_js();
			}
		} else {
			$result = 'failure';
			$msg = 'Can\'t open file ' . $relative_file_path . ' from ' . $href . '. Ensure that you have full write access and that the path is correct.';
		}

		$json->setData(array(
			'result' => $result,
			'filename' => $href,
			'msg' => $msg,
		));
		return $json->update();
	}

	/**
	 * Helper function to sanitize a URL.
	 * Removes cache information from url of CSS files.
	 */
	protected function sanitizeURL($url){
		$result = $url;
		$pos = strpos($url, '.css?');
		if ($pos !== FALSE) {
			$result = substr($url, 0, $pos + 4);
		}
		$pos = strpos($url, '.less?');
		if ($pos !== FALSE) {
			$result = substr($url, 0, $pos + 5);
		}
		return $result;
	}

	/**
	 * Helper function to get the document root for the current Drupal installation.
	 * $_SERVER['DOCUMENT_ROOT'] is not reliable across all systems, so we need a
	 * way to get the correct value.
	 */
	protected function docRoot() {
		$absolute_dir = dirname(__FILE__);
		$relative_dir = drupal_get_path('module', 'live_css');

		/**
		 * Directory structure changed in 8.x - adjusted below to compensate.
		 * If this file moves from the lib/Drupal/live_css/Controller directory,
		 * the number 31 must be adjusted.
		 */
		return drupal_substr($absolute_dir, 0, -1 * (32 + drupal_strlen($relative_dir)));
	}
}
