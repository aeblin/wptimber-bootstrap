<?php

namespace Timber;

class URLHelper {

	/**
	 *
	 *
	 * @return string
	 */
	public static function get_current_url() {
		$pageURL = "http://";
		if ( isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] == "on" ) {
			$pageURL = "https://"; ;
		}
		if ( isset($_SERVER["SERVER_PORT"]) && $_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != "80" ) {
			$pageURL .= self::get_host().":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= self::get_host().$_SERVER["REQUEST_URI"];
		}
		return $pageURL;
	}

	/**
	 *
	 *
	 * @param string  $url
	 * @return bool
	 */
	public static function is_url( $url ) {
		if ( !is_string($url) ) {
			return false;
		}
		$url = strtolower($url);
		if ( strstr($url, '://') ) {
			return true;
		}
		return false;
	}

	/**
	 *
	 *
	 * @return string
	 */
	public static function get_path_base() {
		$struc = get_option('permalink_structure');
		$struc = explode('/', $struc);
		$p = '/';
		foreach ( $struc as $s ) {
			if ( !strstr($s, '%') && strlen($s) ) {
				$p .= $s.'/';
			}
		}
		return $p;
	}

	/**
	 *
	 *
	 * @param string  $url
	 * @param bool    $force
	 * @return string
	 */
	public static function get_rel_url( $url, $force = false ) {
		$url_info = parse_url($url);
		if ( isset($url_info['host']) && $url_info['host'] != self::get_host() && !$force ) {
			return $url;
		}
		$link = '';
		if ( isset($url_info['path']) ) {
			$link = $url_info['path'];
		}
		if ( isset($url_info['query']) && strlen($url_info['query']) ) {
			$link .= '?'.$url_info['query'];
		}
		if ( isset($url_info['fragment']) && strlen($url_info['fragment']) ) {
			$link .= '#'.$url_info['fragment'];
		}
		$link = self::remove_double_slashes($link);
		return $link;
	}

	/**
	 * Some setups like HTTP_HOST, some like SERVER_NAME, it's complicated
	 * @link http://stackoverflow.com/questions/2297403/http-host-vs-server-name
	 * @return string the HTTP_HOST or SERVER_NAME
	 */
	public static function get_host() {
		if ( isset($_SERVER['HTTP_HOST']) ) {
			return $_SERVER['HTTP_HOST'];
		}
		if ( isset($_SERVER['SERVER_NAME']) ) {
			return $_SERVER['SERVER_NAME'];
		}
		return '';
	}

	/**
	 *
	 *
	 * @param string  $url
	 * @return bool
	 */
	public static function is_local( $url ) {
		if ( strstr($url, self::get_host()) ) {
			return true;
		}
		return false;
	}

	/**
	 *
	 *
	 * @param string  $src
	 * @return string
	 */
	public static function get_full_path( $src ) {
		$root = ABSPATH;
		$old_root_path = $root.$src;
		$old_root_path = str_replace('//', '/', $old_root_path);
		return $old_root_path;
	}

	/**
	 * Takes a url and figures out its place based in the file system based on path
	 * NOTE: Not fool-proof, makes a lot of assumptions about the file path
	 * matching the URL path
	 *
	 * @param string  $url
	 * @return string
	 */
	public static function url_to_file_system( $url ) {
		$url_parts = parse_url($url);
		$path = ABSPATH.$url_parts['path'];
		$path = str_replace('//', '/', $path);
		return $path;
	}

	/**
	 * @param string $fs
	 */
	public static function file_system_to_url( $fs ) {
		$relative_path = self::get_rel_path($fs);
		$home = home_url('/'.$relative_path);
		return $home;
	}

	/**
	 *
	 *
	 * @param string  $src
	 * @return string
	 */
	public static function get_rel_path( $src ) {
		if ( strstr($src, ABSPATH) ) {
			return str_replace(ABSPATH, '', $src);
		}
		//its outside the wordpress directory, alternate setups:
		$src = str_replace(WP_CONTENT_DIR, '', $src);
		return WP_CONTENT_SUBDIR.$src;
	}

	/**
	 *
	 *
	 * @param string  $url
	 * @return string
	 */
	public static function remove_double_slashes( $url ) {
		$url = str_replace('//', '/', $url);
		if ( strstr($url, 'http:') && !strstr($url, 'http://') ) {
			$url = str_replace('http:/', 'http://', $url);
		}
		return $url;
	}

	/**
	 *
	 *
	 * @param string  $url
	 * @param string  $path
	 * @return string
	 */
	public static function prepend_to_url( $url, $path ) {
		if ( strstr(strtolower($url), 'http') ) {
			$url_parts = parse_url($url);
			$url = $url_parts['scheme'].'://'.$url_parts['host'].$path.$url_parts['path'];
			if ( isset($url_parts['query']) ) {
				$url .= $url_parts['query'];
			}
			if ( isset($url_parts['fragment']) ) {
				$url .= $url_parts['fragment'];
			}
		} else {
			$url = $url.$path;
		}
		return self::remove_double_slashes($url);
	}

	/**
	 *
	 *
	 * @param string  $path
	 * @return string
	 */
	public static function preslashit( $path ) {
		if ( strpos($path, '/') != 0 ) {
			$path = '/'.$path;
		}
		return $path;
	}

	/**
	 * This will evaluate wheter a URL is at an aboslute location (like http://example.org/whatever)
	 *
	 * @param string $path
	 * @return boolean true if $path is an absolute url, false if relative.
	 */
	public static function is_absolute( $path ) {
		return (boolean) (strstr($path, 'http'));
	}


	/**
	 * This function is slightly different from the one below in the case of:
	 * an image hosted on the same domain BUT on a different site than the
	 * Wordpress install will be reported as external content.
	 *
	 * @param string  $url a URL to evaluate against
	 * @return boolean if $url points to an external location returns true
	 */
	public static function is_external_content( $url ) {
		$is_external = self::is_absolute($url) && !self::is_internal_content($url);

		return $is_external;
	}

	/**
	 * @param string $url
	 */
	private static function is_internal_content( $url ) {
		// using content_url() instead of site_url or home_url is IMPORTANT
		// otherwise you run into errors with sites that:
		// 1. use WPML plugin
		// 2. or redefine content directory
		$is_content_url = strstr($url, content_url());

		// this case covers when the upload directory has been redefined
		$upload_dir = wp_upload_dir();
		$is_upload_url = strstr($url, $upload_dir['baseurl']);

		return $is_content_url || $is_upload_url;
	}
    
	/**
	 *
	 *
	 * @param string  $url
	 * @return bool     true if $path is an external url, false if relative or local.
	 *                  true if it's a subdomain (http://cdn.example.org = true)
	 */
	public static function is_external( $url ) {
		$has_http = strstr(strtolower($url), 'http');
		$on_domain = strstr($url, self::get_host());
		if ( $has_http && !$on_domain ) {
			return true;
		}
		return false;
	}

	/**
	 * Pass links through untrailingslashit unless they are a single /
	 *
	 * @param string  $link
	 * @return string
	 */
	public static function remove_trailing_slash( $link ) {
		if ( $link != "/" ) {
			$link = untrailingslashit($link);
		}
		return $link;
	}

	/**
	 * Returns the url parameters, for example for url http://example.org/blog/post/news/2014/whatever
	 * this will return array('blog', 'post', 'news', '2014', 'whatever');
	 * OR if sent an integer like: TimberUrlHelper::get_params(2); this will return 'news';
	 *
	 * @param int $i the position of the parameter to grab.
	 * @return array|string
	 */
	public static function get_params( $i = false ) {
		$args = explode('/', trim(strtolower($_SERVER['REQUEST_URI'])));
		$newargs = array();
		foreach ( $args as $arg ) {
			if ( strlen($arg) ) {
				$newargs[] = $arg;
			}
		}
		if ( $i === false ) {
			return $newargs;
		}
		if ( $i < 0 ) {
			//count from end
			$i = count($newargs) + $i;
		}
		if ( isset($newargs[$i]) ) {
			return $newargs[$i];
		}
	}

}
