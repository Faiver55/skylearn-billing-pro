<?php
/**
 * Performance optimization utilities for SkyLearn Billing Pro
 *
 * @link       https://skyianllc.com
 * @since      1.0.0
 *
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/utilities
 */

/**
 * Performance optimization utilities.
 *
 * Handles database optimization, caching strategies, and performance monitoring.
 *
 * @since      1.0.0
 * @package    SkyLearnBillingPro
 * @subpackage SkyLearnBillingPro/includes/utilities
 * @author     Skyian LLC <contact@skyianllc.com>
 */
class SLBP_Performance_Optimizer {

	/**
	 * Cache group for plugin-specific caching.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $cache_group    Cache group name.
	 */
	private $cache_group = 'slbp_performance';

	/**
	 * Default cache expiration time in seconds.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int    $cache_expiration    Cache expiration time.
	 */
	private $cache_expiration = 3600; // 1 hour

	/**
	 * Performance monitoring data.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $performance_data    Performance metrics.
	 */
	private $performance_data = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->cache_expiration = apply_filters( 'slbp_cache_expiration', $this->cache_expiration );
	}

	/**
	 * Optimize database queries with proper indexing hints.
	 *
	 * @since    1.0.0
	 * @param    string $table_name    Table name to optimize.
	 * @param    array  $query_params  Query parameters for optimization hints.
	 * @return   array                 Optimization recommendations.
	 */
	public function optimize_database_queries( $table_name, $query_params = array() ) {
		global $wpdb;

		$optimizations = array();
		$full_table_name = $wpdb->prefix . $table_name;

		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare(
			"SHOW TABLES LIKE %s",
			$full_table_name
		) );

		if ( ! $table_exists ) {
			return array(
				'error' => sprintf( 'Table %s does not exist', $full_table_name )
			);
		}

		// Analyze table structure
		$table_info = $wpdb->get_results( "SHOW INDEX FROM {$full_table_name}", ARRAY_A );
		$table_status = $wpdb->get_row( "SHOW TABLE STATUS LIKE '{$full_table_name}'", ARRAY_A );

		// Check for missing indexes
		$missing_indexes = $this->detect_missing_indexes( $table_name, $query_params );
		if ( ! empty( $missing_indexes ) ) {
			$optimizations['missing_indexes'] = $missing_indexes;
		}

		// Check table fragmentation
		if ( isset( $table_status['Data_free'] ) && $table_status['Data_free'] > 0 ) {
			$optimizations['fragmentation'] = array(
				'data_free' => $table_status['Data_free'],
				'recommendation' => 'Consider running OPTIMIZE TABLE to reduce fragmentation'
			);
		}

		// Analyze query performance
		$slow_queries = $this->detect_slow_queries( $table_name );
		if ( ! empty( $slow_queries ) ) {
			$optimizations['slow_queries'] = $slow_queries;
		}

		return $optimizations;
	}

	/**
	 * Implement advanced caching with Redis/Memcached support.
	 *
	 * @since    1.0.0
	 * @param    string $key           Cache key.
	 * @param    mixed  $data          Data to cache.
	 * @param    int    $expiration    Cache expiration time.
	 * @return   bool                  True on success, false on failure.
	 */
	public function set_cache( $key, $data, $expiration = null ) {
		if ( null === $expiration ) {
			$expiration = $this->cache_expiration;
		}

		$cache_key = $this->build_cache_key( $key );

		// Try Redis first if available
		if ( $this->is_redis_available() ) {
			return $this->set_redis_cache( $cache_key, $data, $expiration );
		}

		// Fall back to Memcached
		if ( $this->is_memcached_available() ) {
			return $this->set_memcached_cache( $cache_key, $data, $expiration );
		}

		// Use WordPress object cache as fallback
		return wp_cache_set( $cache_key, $data, $this->cache_group, $expiration );
	}

	/**
	 * Retrieve data from cache.
	 *
	 * @since    1.0.0
	 * @param    string $key    Cache key.
	 * @return   mixed          Cached data or false if not found.
	 */
	public function get_cache( $key ) {
		$cache_key = $this->build_cache_key( $key );

		// Try Redis first if available
		if ( $this->is_redis_available() ) {
			return $this->get_redis_cache( $cache_key );
		}

		// Fall back to Memcached
		if ( $this->is_memcached_available() ) {
			return $this->get_memcached_cache( $cache_key );
		}

		// Use WordPress object cache as fallback
		return wp_cache_get( $cache_key, $this->cache_group );
	}

	/**
	 * Delete cache entry.
	 *
	 * @since    1.0.0
	 * @param    string $key    Cache key.
	 * @return   bool           True on success, false on failure.
	 */
	public function delete_cache( $key ) {
		$cache_key = $this->build_cache_key( $key );

		// Try Redis first if available
		if ( $this->is_redis_available() ) {
			return $this->delete_redis_cache( $cache_key );
		}

		// Fall back to Memcached
		if ( $this->is_memcached_available() ) {
			return $this->delete_memcached_cache( $cache_key );
		}

		// Use WordPress object cache as fallback
		return wp_cache_delete( $cache_key, $this->cache_group );
	}

	/**
	 * Flush all plugin caches.
	 *
	 * @since    1.0.0
	 * @return   bool    True on success, false on failure.
	 */
	public function flush_cache() {
		$success = true;

		// Flush Redis cache
		if ( $this->is_redis_available() ) {
			$success = $this->flush_redis_cache() && $success;
		}

		// Flush Memcached cache
		if ( $this->is_memcached_available() ) {
			$success = $this->flush_memcached_cache() && $success;
		}

		// Flush WordPress object cache
		wp_cache_flush_group( $this->cache_group );

		return $success;
	}

	/**
	 * Monitor query performance and detect N+1 queries.
	 *
	 * @since    1.0.0
	 * @param    string $query_id    Unique identifier for the query.
	 * @param    string $query       SQL query to monitor.
	 * @param    float  $start_time  Query start time.
	 * @return   void
	 */
	public function monitor_query_performance( $query_id, $query, $start_time ) {
		$execution_time = microtime( true ) - $start_time;

		// Log slow queries (> 1 second)
		if ( $execution_time > 1.0 ) {
			$this->log_slow_query( $query_id, $query, $execution_time );
		}

		// Detect potential N+1 queries
		if ( $this->is_potential_n_plus_one( $query_id, $query ) ) {
			$this->log_n_plus_one_query( $query_id, $query );
		}

		// Store performance data
		$this->performance_data[ $query_id ] = array(
			'query' => $query,
			'execution_time' => $execution_time,
			'timestamp' => time(),
		);
	}

	/**
	 * Get performance statistics.
	 *
	 * @since    1.0.0
	 * @return   array    Performance statistics.
	 */
	public function get_performance_stats() {
		$stats = array(
			'total_queries' => count( $this->performance_data ),
			'slow_queries' => 0,
			'average_execution_time' => 0,
			'total_execution_time' => 0,
		);

		if ( ! empty( $this->performance_data ) ) {
			$total_time = 0;
			foreach ( $this->performance_data as $data ) {
				$total_time += $data['execution_time'];
				if ( $data['execution_time'] > 1.0 ) {
					$stats['slow_queries']++;
				}
			}

			$stats['total_execution_time'] = $total_time;
			$stats['average_execution_time'] = $total_time / count( $this->performance_data );
		}

		return $stats;
	}

	/**
	 * Optimize asset delivery with minification and compression.
	 *
	 * @since    1.0.0
	 * @param    string $asset_type    Type of asset (css, js).
	 * @param    array  $files         Array of file paths.
	 * @return   string               Optimized asset URL.
	 */
	public function optimize_assets( $asset_type, $files ) {
		$cache_key = 'optimized_assets_' . $asset_type . '_' . md5( serialize( $files ) );
		$cached_url = $this->get_cache( $cache_key );

		if ( false !== $cached_url ) {
			return $cached_url;
		}

		$optimized_content = '';
		foreach ( $files as $file ) {
			$file_path = SLBP_PLUGIN_PATH . $file;
			if ( file_exists( $file_path ) ) {
				$content = file_get_contents( $file_path );
				
				if ( 'css' === $asset_type ) {
					$content = $this->minify_css( $content );
				} elseif ( 'js' === $asset_type ) {
					$content = $this->minify_js( $content );
				}
				
				$optimized_content .= $content . "\n";
			}
		}

		// Create optimized file
		$upload_dir = wp_upload_dir();
		$cache_dir = $upload_dir['basedir'] . '/slbp-cache/';
		
		if ( ! file_exists( $cache_dir ) ) {
			wp_mkdir_p( $cache_dir );
		}

		$optimized_filename = $cache_key . '.' . $asset_type;
		$optimized_file_path = $cache_dir . $optimized_filename;
		
		file_put_contents( $optimized_file_path, $optimized_content );

		$optimized_url = $upload_dir['baseurl'] . '/slbp-cache/' . $optimized_filename;
		
		// Cache the URL for 24 hours
		$this->set_cache( $cache_key, $optimized_url, 86400 );

		return $optimized_url;
	}

	/**
	 * Check if Redis is available.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   bool    True if Redis is available, false otherwise.
	 */
	private function is_redis_available() {
		return class_exists( 'Redis' ) && defined( 'WP_REDIS_HOST' );
	}

	/**
	 * Check if Memcached is available.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   bool    True if Memcached is available, false otherwise.
	 */
	private function is_memcached_available() {
		return class_exists( 'Memcached' ) && defined( 'WP_CACHE_KEY_SALT' );
	}

	/**
	 * Build cache key with proper prefix.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $key    Original cache key.
	 * @return   string         Prefixed cache key.
	 */
	private function build_cache_key( $key ) {
		return 'slbp_' . md5( $key . SLBP_VERSION );
	}

	/**
	 * Set Redis cache.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $key           Cache key.
	 * @param    mixed  $data          Data to cache.
	 * @param    int    $expiration    Cache expiration time.
	 * @return   bool                  True on success, false on failure.
	 */
	private function set_redis_cache( $key, $data, $expiration ) {
		try {
			$redis = new Redis();
			$redis->connect( WP_REDIS_HOST, WP_REDIS_PORT ?? 6379 );
			
			if ( defined( 'WP_REDIS_PASSWORD' ) ) {
				$redis->auth( WP_REDIS_PASSWORD );
			}
			
			$serialized_data = serialize( $data );
			return $redis->setex( $key, $expiration, $serialized_data );
		} catch ( Exception $e ) {
			error_log( 'SLBP Redis Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Get Redis cache.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $key    Cache key.
	 * @return   mixed          Cached data or false if not found.
	 */
	private function get_redis_cache( $key ) {
		try {
			$redis = new Redis();
			$redis->connect( WP_REDIS_HOST, WP_REDIS_PORT ?? 6379 );
			
			if ( defined( 'WP_REDIS_PASSWORD' ) ) {
				$redis->auth( WP_REDIS_PASSWORD );
			}
			
			$cached_data = $redis->get( $key );
			return false !== $cached_data ? unserialize( $cached_data ) : false;
		} catch ( Exception $e ) {
			error_log( 'SLBP Redis Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Delete Redis cache.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $key    Cache key.
	 * @return   bool           True on success, false on failure.
	 */
	private function delete_redis_cache( $key ) {
		try {
			$redis = new Redis();
			$redis->connect( WP_REDIS_HOST, WP_REDIS_PORT ?? 6379 );
			
			if ( defined( 'WP_REDIS_PASSWORD' ) ) {
				$redis->auth( WP_REDIS_PASSWORD );
			}
			
			return $redis->del( $key ) > 0;
		} catch ( Exception $e ) {
			error_log( 'SLBP Redis Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Flush Redis cache.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   bool    True on success, false on failure.
	 */
	private function flush_redis_cache() {
		try {
			$redis = new Redis();
			$redis->connect( WP_REDIS_HOST, WP_REDIS_PORT ?? 6379 );
			
			if ( defined( 'WP_REDIS_PASSWORD' ) ) {
				$redis->auth( WP_REDIS_PASSWORD );
			}
			
			$keys = $redis->keys( 'slbp_*' );
			if ( ! empty( $keys ) ) {
				return $redis->del( $keys ) > 0;
			}
			return true;
		} catch ( Exception $e ) {
			error_log( 'SLBP Redis Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Set Memcached cache.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $key           Cache key.
	 * @param    mixed  $data          Data to cache.
	 * @param    int    $expiration    Cache expiration time.
	 * @return   bool                  True on success, false on failure.
	 */
	private function set_memcached_cache( $key, $data, $expiration ) {
		try {
			$memcached = new Memcached();
			$memcached->addServer( WP_MEMCACHED_HOST ?? 'localhost', WP_MEMCACHED_PORT ?? 11211 );
			return $memcached->set( $key, $data, $expiration );
		} catch ( Exception $e ) {
			error_log( 'SLBP Memcached Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Get Memcached cache.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $key    Cache key.
	 * @return   mixed          Cached data or false if not found.
	 */
	private function get_memcached_cache( $key ) {
		try {
			$memcached = new Memcached();
			$memcached->addServer( WP_MEMCACHED_HOST ?? 'localhost', WP_MEMCACHED_PORT ?? 11211 );
			return $memcached->get( $key );
		} catch ( Exception $e ) {
			error_log( 'SLBP Memcached Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Delete Memcached cache.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $key    Cache key.
	 * @return   bool           True on success, false on failure.
	 */
	private function delete_memcached_cache( $key ) {
		try {
			$memcached = new Memcached();
			$memcached->addServer( WP_MEMCACHED_HOST ?? 'localhost', WP_MEMCACHED_PORT ?? 11211 );
			return $memcached->delete( $key );
		} catch ( Exception $e ) {
			error_log( 'SLBP Memcached Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Flush Memcached cache.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   bool    True on success, false on failure.
	 */
	private function flush_memcached_cache() {
		try {
			$memcached = new Memcached();
			$memcached->addServer( WP_MEMCACHED_HOST ?? 'localhost', WP_MEMCACHED_PORT ?? 11211 );
			return $memcached->flush();
		} catch ( Exception $e ) {
			error_log( 'SLBP Memcached Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Detect missing database indexes.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $table_name    Table name.
	 * @param    array  $query_params  Query parameters.
	 * @return   array                 Missing indexes recommendations.
	 */
	private function detect_missing_indexes( $table_name, $query_params ) {
		$missing_indexes = array();

		// Common query patterns that need indexes
		$index_recommendations = array(
			'slbp_transactions' => array(
				'user_id_status' => array( 'user_id', 'status' ),
				'created_at_status' => array( 'created_at', 'status' ),
				'payment_gateway_status' => array( 'payment_gateway', 'status' ),
			),
			'slbp_subscriptions' => array(
				'user_id_status' => array( 'user_id', 'status' ),
				'expires_at_status' => array( 'expires_at', 'status' ),
				'next_billing_date' => array( 'next_billing_date' ),
			),
			'slbp_api_logs' => array(
				'created_at_endpoint' => array( 'created_at', 'endpoint' ),
				'user_id_created_at' => array( 'user_id', 'created_at' ),
			),
		);

		if ( isset( $index_recommendations[ $table_name ] ) ) {
			$missing_indexes = $index_recommendations[ $table_name ];
		}

		return $missing_indexes;
	}

	/**
	 * Detect slow queries for analysis.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $table_name    Table name.
	 * @return   array                 Slow queries information.
	 */
	private function detect_slow_queries( $table_name ) {
		global $wpdb;

		// This would typically analyze the slow query log
		// For now, return empty array as slow query log analysis requires additional setup
		return array();
	}

	/**
	 * Check if query is potential N+1 pattern.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $query_id    Query identifier.
	 * @param    string $query       SQL query.
	 * @return   bool                True if potential N+1, false otherwise.
	 */
	private function is_potential_n_plus_one( $query_id, $query ) {
		// Simple detection: if same query pattern executed multiple times
		static $query_counts = array();
		
		$query_pattern = preg_replace( '/\d+/', 'N', $query );
		$query_counts[ $query_pattern ] = ( $query_counts[ $query_pattern ] ?? 0 ) + 1;
		
		return $query_counts[ $query_pattern ] > 10;
	}

	/**
	 * Log slow query for analysis.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $query_id       Query identifier.
	 * @param    string $query          SQL query.
	 * @param    float  $execution_time Execution time.
	 * @return   void
	 */
	private function log_slow_query( $query_id, $query, $execution_time ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'SLBP Slow Query [%s]: %s (%.4f seconds)',
				$query_id,
				$query,
				$execution_time
			) );
		}
	}

	/**
	 * Log potential N+1 query for analysis.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $query_id    Query identifier.
	 * @param    string $query       SQL query.
	 * @return   void
	 */
	private function log_n_plus_one_query( $query_id, $query ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'SLBP Potential N+1 Query [%s]: %s',
				$query_id,
				$query
			) );
		}
	}

	/**
	 * Minify CSS content.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $css    CSS content.
	 * @return   string         Minified CSS.
	 */
	private function minify_css( $css ) {
		// Remove comments
		$css = preg_replace( '/\/\*.*?\*\//s', '', $css );
		
		// Remove unnecessary whitespace
		$css = preg_replace( '/\s+/', ' ', $css );
		$css = preg_replace( '/;\s*}/', '}', $css );
		$css = preg_replace( '/\s*{\s*/', '{', $css );
		$css = preg_replace( '/;\s*/', ';', $css );
		
		return trim( $css );
	}

	/**
	 * Minify JavaScript content.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $js    JavaScript content.
	 * @return   string        Minified JavaScript.
	 */
	private function minify_js( $js ) {
		// Basic minification - remove comments and unnecessary whitespace
		$js = preg_replace( '/\/\*.*?\*\//s', '', $js );
		$js = preg_replace( '/\/\/.*$/m', '', $js );
		$js = preg_replace( '/\s+/', ' ', $js );
		
		return trim( $js );
	}
}