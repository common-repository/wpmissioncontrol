<?php
if ( !defined( 'ABSPATH' ) ) {
	exit;
}
class WPMC_Security {

	public function get_core_files_checksum_data() {
		$scanned_files = self::scan_dir( ABSPATH, array( 'wp-content' ) );
		$result = array();

		foreach ( $scanned_files as $path => $file ) {
			$result[$path] = md5_file( $file );
		}

		return $result;
	}

	public function get_themes_files_checksum_data() {
		$themes = wp_get_themes();
		$result = array();

		foreach ( $themes as $theme ) {
			$theme_data = array(
				'name'		=> $theme->Name,
				'version'	=> $theme->Version,
				'files'		=> array()
			);

			$theme_folder_name = $theme->get_stylesheet();
			$files = $theme->get_files( null, -1 );
			
			foreach ( $files as $relative_path => $full_path ) {
				$theme_data['files'][$theme_folder_name . DIRECTORY_SEPARATOR . $relative_path] = md5_file( $full_path );
			}

			array_push( $result, $theme_data );
		}

		return $result;
	}

	public function get_plugins_files_checksum_data() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins = get_plugins();
		$result = array();

		foreach ( $plugins as $plugin_slug => $plugin_data ) {

			$plugin_data = array(
				'name'		=> self::get_plugin_name( $plugin_slug ),
				'version'	=> !empty( $plugin_data['Version'] ) ? $plugin_data['Version'] : false,
				'files'		=> array()
			);

			$plugin_path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin_slug;
			$plugin_folder_struct = explode( DIRECTORY_SEPARATOR, $plugin_slug );

			if ( count( $plugin_folder_struct ) > 1 ) {
				$plugin_path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname( $plugin_slug );

				$scanned_files = self::scan_dir( $plugin_path, array() );
				foreach ( $scanned_files as $path => $file ) {
					$relative_path = trim( str_replace( WP_PLUGIN_DIR, '', ABSPATH . $path ), DIRECTORY_SEPARATOR );
					$plugin_data['files'][$relative_path] = md5_file( $file );
				}
			} else {
				$plugin_data['files'][basename( $plugin_path )] = md5_file( $plugin_path );
			}

			array_push( $result, $plugin_data );
		}

		return $result;
	}

	public static function is_shell_exec_enabled() {
		return is_callable( 'shell_exec' ) && false === stripos( ini_get( 'disable_functions' ), 'shell_exec' );
	}		

	private static function scan_dir( $path, $exclude = array(), $extension = false ) {
        $result = [];
        if ( !in_array( '.', $exclude ) ) {
        	array_push( $exclude, '.' );
        }
        if ( !in_array( '..', $exclude ) ) {
        	array_push( $exclude, '..' );
        }
  		foreach ( scandir( $path ) as $filename ) {
  			if ( in_array( $filename, $exclude ) ) {
  				continue;
  			}
    		$file_path = rtrim( $path, '/' ) . DIRECTORY_SEPARATOR . $filename;
    		if ( is_dir( $file_path ) ) {
    			$result = array_merge( $result, self::scan_dir( $file_path, $exclude, $extension ) );
    		} else {
    			if ( $extension && pathinfo( $file_path, PATHINFO_EXTENSION ) != $extension ) {
    				continue;
    			}
      			$result[str_replace( ABSPATH, '', $file_path )] = $file_path;
    		}
		}
  		return $result;
    }

    private static function get_plugin_name( $basename ) {
		if ( false === strpos( $basename, '/' ) ) {
			$name = basename( $basename, '.php' );
		} else {
			$name = dirname( $basename );
		}

		return $name;
	}

	public function scan_files() {
		$files_to_process = get_transient( 'wpmc_scanned_files' );
		if ( !$files_to_process ) {
			$scanned_files = self::scan_dir( WP_CONTENT_DIR );
			$files_to_process = array();
			$extensions = array(
				'php',
				// 'js',
				'htaccess'
			);
			$excludes = array(
				'wp-config.php',
			);
			foreach ( $scanned_files as $path => $file ) {
				$ext = pathinfo( $file, PATHINFO_EXTENSION );
				if ( in_array( $ext, $extensions ) || in_array( basename( $file ), $excludes ) ) {
					$files_to_process[$path] = $file;
				}
			}
			set_transient( 'wpmc_scanned_files', $files_to_process, 1 * DAY_IN_SECONDS );
		}
		return array(
			'success'				=> true,
			'message'				=> 'Files scanned successfully',
			'total_files_count'		=> count( $files_to_process ),
		);
	}

	public function prepare_combined_file( $params ) {
		$upload_dir = wp_get_upload_dir();
		if ( $upload_dir['error'] ) {
			return array(
				'success'	=> false,
				'message'	=> $upload_dir['error']
			);
		}

		$output_dir = $upload_dir['path'] . '/wpmc_files_export';
		if ( !file_exists( $output_dir ) ) {
			mkdir( $output_dir );
		}

		$output_file = $output_dir . '/wpmc_combined_files.txt';
		$file_mode = 'a';
		if ( empty( $params['offset'] ) || $params['offset'] == 0 ) {
			$file_mode = 'w';
			if ( file_exists( $output_file ) ) {
				unlink( $output_file );
				$other_files = self::scan_dir( $output_dir );
				foreach ( $other_files as $path => $file ) {
					if ( pathinfo( $file, PATHINFO_EXTENSION ) != 'txt' ) {
						unlink( $path );
					}
				}
			}
			$htaccess_file = $output_dir . '/.htaccess';
			$htaccess = fopen( $htaccess_file, 'w' );
			if ( !$htaccess ) {
				return array(
					'success'	=> false,
					'message'	=> 'Failed to open htaccess file'
				);
			}
			fwrite( $htaccess, 'order deny,allow' . PHP_EOL . 'deny from all' );
			fclose( $htaccess );
		}

		$output = fopen( $output_file, $file_mode );
		if ( !$output ) {
			return array(
				'success'	=> false,
				'message'	=> 'Failed to open output file'
			);
		}

		$files_to_process = get_transient( 'wpmc_scanned_files' );
		if ( !$files_to_process ) {
			$scanned_files = self::scan_dir( ABSPATH );
			$files_to_process = array();
			$extensions = array(
				'php',
				// 'js',
				'htaccess'
			);
			$excludes = array(
				'wp-config.php',
			);
			foreach ( $scanned_files as $path => $file ) {
				$ext = pathinfo( $file, PATHINFO_EXTENSION );
				if ( in_array( $ext, $extensions ) || in_array( basename( $file ), $excludes ) ) {
					$files_to_process[$path] = $file;
				}
			}
			set_transient( 'wpmc_scanned_files', $files_to_process, 1 * DAY_IN_SECONDS );
		}
		$total_files_count = count( $files_to_process );
		$offset = 0;
		if ( !empty( $params['offset'] ) ) {
			$offset = $params['offset'];
		}
		$limit = 10000;
		if ( !empty( $params['limit'] ) ) {
			$limit = $params['limit'];
		}
		$files_to_process = array_slice( $files_to_process, $offset, $limit );
		if ( count( $files_to_process ) == 0 ) {
			return array(
				'success'		=> false,
				'message'		=> 'No files in range',
			);
		}

		$result = array();
		$files_count = 0;
		foreach ( $files_to_process as $path => $file ) {
			$contents = file_get_contents( $file );
			$checksum = md5_file( $file );
	        fwrite( $output, "[FILE_START: $file [FILE_CHECKSUM: $checksum]]\n" );
	        fwrite( $output, $contents . "\n" );
	        fwrite( $output, "[FILE_END]\n" );
	        $files_count++;
		}
		fclose( $output );

		return array(
			'success'				=> true,
			'message'				=> 'Output file generated successfully',
			'files_count'			=> $files_count,
			'total_files_count'		=> $total_files_count,
		);
	}

	public function serve_combined_file() {
		$upload_dir = wp_get_upload_dir();
		if ( $upload_dir['error'] ) {
			return array(
				'success'	=> false,
				'message'	=> $upload_dir['error']
			);
		}
		$output_dir = $upload_dir['path'] . '/wpmc_files_export';
		$output_file = $output_dir . '/wpmc_combined_files.txt';

		$zip_file = $output_dir . '/wpmc_combined_files.zip';
		$zip = new ZipArchive();
    	if ( $zip->open( $zip_file, ZipArchive::CREATE ) === TRUE ) {
	        $zip->addFile( $output_file, basename( $output_file ) );
	        $zip->close();
	    } else {
	        throw new Exception("Cannot create zip file.");
	    }

	    ob_start();
	    readfile( $zip_file );
	    $result = array(
	    	'success'	=> true,
	    	'stream'	=> ob_get_clean(),
	    	'length'	=> filesize( $zip_file ),
	    	'filename'	=> basename( $zip_file ),
	    );

	    unlink( $output_file );
	    unlink( $zip_file );

		return $result;
	}

}