<?php
/**
 * Manages database table for redirects.
 *
 * @package SmartCrawl
 */

namespace SmartCrawl\Modules\Advanced\Redirects;

use SmartCrawl\Integration\Maxmind\GeoDB;
use SmartCrawl\Singleton;
use stdClass;

/**
 * Class to manage redirects db table.
 */
class Database_Table {

	use Singleton;

	/**
	 * DB Table version.
	 *
	 * @var string
	 */
	private $version = '1.0.0';

	/**
	 * Creates table.
	 *
	 * @return void
	 */
	public function create_table() {
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		if ( ! function_exists( '\dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		dbDelta(
			"CREATE TABLE {$wpdb->prefix}smartcrawl_redirects (
			id bigint UNSIGNED NOT NULL auto_increment,
			title varchar(200) NOT NULL DEFAULT '',
    		source varchar(500) NOT NULL DEFAULT '',
    		path varchar(500) NOT NULL DEFAULT '',
    		destination varchar(200) NOT NULL DEFAULT '',
    		type smallint NOT NULL DEFAULT 0,
			options varchar(500) NOT NULL DEFAULT '',
			rules varchar(500) NOT NULL DEFAULT '',
		  	PRIMARY KEY  (id)
		) $collate;"
		);

		update_option( "{$wpdb->prefix}smartcrawl_redirects_version", $this->version );
	}

	/**
	 * Upgrades table.
	 *
	 * @return void
	 */
	public function upgrade_table() {
		$redirects = $this->get_redirects();

		if ( ! $redirects ) {
			return;
		}

		foreach ( $redirects as $redirect ) {
			$options = $redirect->get_options();

			foreach ( array( 'page', 'post' ) as $post_type ) {
				if ( in_array( $post_type, $options, true ) ) {
					$redirect->set_destination(
						array(
							'id'   => $redirect->get_destination(),
							'type' => $post_type,
						)
					);

					unset( $options[ $post_type ] );
				}
			}

			$redirect->set_options( $options );

			$this->save_redirect( $redirect );
		}
	}

	/**
	 * Returns table name.
	 *
	 * @return string
	 */
	public function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'smartcrawl_redirects';
	}

	/**
	 * Checks if table exists.
	 *
	 * @return string|null
	 */
	public function table_exists() {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s;', $this->get_table_name() ) );
	}

	/**
	 * Deletes all redirects from table.
	 *
	 * @return int|false
	 */
	public function delete_all() {
		global $wpdb;

		return $wpdb->query( "DELETE FROM {$wpdb->prefix}smartcrawl_redirects" );
	}

	/**
	 * Drops table.
	 *
	 * @return bool
	 */
	public function drop_table() {
		global $wpdb;

		return $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}smartcrawl_redirects" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
	}

	/**
	 * Retrieves redirect data by id.
	 * This method is used in unit testing to check CRUD operation.
	 *
	 * @param int $id Redirect ID.
	 *
	 * @return Item|null
	 */
	public function get_redirect( $id ) {
		global $wpdb;

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}smartcrawl_redirects WHERE id = %d", $id ) );

		return $this->map_row_to_model( $row );
	}

	/**
	 * Retrieves redirect data by source, not regex.
	 *
	 * @param string $source Source.
	 *
	 * @return Item|null
	 */
	public function get_redirect_by_source( string $source ) {
		if ( ! $this->table_exists() ) {
			return null;
		}

		global $wpdb;

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}smartcrawl_redirects WHERE source = %s", $source ) );

		return $this->map_row_to_model( $row );
	}

	/**
	 * Retrieves redirect data by regex source.
	 *
	 * @param string $source Source.
	 *
	 * @return Item[]
	 */
	public function get_redirects_by_source_regex( $source ) {
		global $wpdb;

		$redirects = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}smartcrawl_redirects WHERE path = 'regex' AND %s RLIKE source", $source ) );

		return $redirects ? array_map( array( $this, 'map_row_to_model' ), $redirects ) : array();
	}

	/**
	 * Retrieves redirects by path.
	 *
	 * @param string $path Path.
	 *
	 * @return Item[]
	 */
	public function get_redirects_by_path( $path ) {
		global $wpdb;

		$redirects = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}smartcrawl_redirects WHERE path = %s", $path ) );

		return $redirects ? array_map( array( $this, 'map_row_to_model' ), $redirects ) : array();
	}

	/**
	 * Retrieves redirects raw data.
	 *
	 * @param array $ids Redirect IDs.
	 *
	 * @return stdClass[]
	 */
	private function get_raw_redirects( $ids ) {
		global $wpdb;

		if ( ! $this->table_exists() ) {
			return array();
		}

		if ( empty( $ids ) ) {
			$redirects = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}smartcrawl_redirects", OBJECT_K );
		} else {
			$ids = implode( ',', array_filter( array_map( 'intval', $ids ) ) );

			$redirects = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}smartcrawl_redirects WHERE id in ( $ids )", OBJECT_K ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		return empty( $redirects ) ? array() : $redirects;
	}

	/**
	 * Retrieves deflated redirects.
	 *
	 * @param array $ids Redirect IDs.
	 *
	 * @return array
	 */
	public function get_deflated_redirects( $ids = array() ) {
		$redirects = $this->get_raw_redirects( $ids );

		return array_map( array( $this, 'map_row_to_deflated' ), $redirects );
	}

	/**
	 * Retrieves redirects by IDs.
	 *
	 * @param array $ids Redirect IDs.
	 *
	 * @return Item[]|false
	 */
	public function get_redirects( $ids = array() ) {
		$redirects = $this->get_raw_redirects( $ids );

		return $redirects
			? array_map( array( $this, 'map_row_to_model' ), $redirects )
			: false;
	}

	/**
	 * Retrieves total number of redirects.
	 *
	 * @return int
	 *
	 * @since 3.7.0
	 */
	public function get_redirect_count() {
		global $wpdb;

		return (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}smartcrawl_redirects" );
	}

	/**
	 * Deletes redirects from table.
	 *
	 * @param bool|array $ids Redirect IDs to be deleted.
	 *
	 * @return false|int
	 */
	public function delete_redirects( $ids = false ) {
		global $wpdb;

		if ( empty( $ids ) ) {
			return $this->delete_all();
		}

		if ( ! is_array( $ids ) ) {
			return false;
		}

		$ids = implode( ',', array_filter( array_map( 'intval', $ids ) ) );

		return $wpdb->query( "DELETE FROM {$wpdb->prefix}smartcrawl_redirects WHERE id IN ($ids)" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Saves a redirect.
	 *
	 * @param Item $redirect Redirect item.
	 *
	 * @return false|int
	 */
	public function save_redirect( $redirect ) {
		global $wpdb;

		$old_row = array();
		$new_row = $this->map_model_to_row( $redirect );

		if ( $redirect->get_id() ) {
			$id = $redirect->get_id();

			$old_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}smartcrawl_redirects WHERE id = %d", $id ), ARRAY_A );

			$wpdb->update(
				$wpdb->prefix . 'smartcrawl_redirects',
				$new_row,
				array( 'id' => $redirect->get_id() ),
				$this->formats()
			);
		} else {
			$inserted = $wpdb->insert(
				$wpdb->prefix . 'smartcrawl_redirects',
				$new_row,
				$this->formats()
			);

			$id = $inserted ? $wpdb->insert_id : false;
		}

		do_action( 'smartcrawl_after_save_redirect', $old_row, $new_row );

		return $id;
	}

	/**
	 * Inserts multiple redirects to table.
	 *
	 * @param Item[] $redirects Redirect items.
	 *
	 * @return bool|int
	 */
	public function insert_redirects( $redirects ) {
		global $wpdb;

		$values = array();
		foreach ( $redirects as $redirect ) {
			$values[] = array(
				'title'       => $redirect->get_title(),
				'source'      => $redirect->get_source(),
				'path'        => $redirect->get_path(),
				'destination' => $redirect->get_destination(),
				'type'        => $redirect->get_type(),
				'options'     => $this->options_to_string( $redirect->get_options() ),
				'rules'       => $redirect->get_rules(),
			);
		}

		if ( empty( $values ) ) {
			return 0;
		}

		$values = array();

		foreach ( $redirects as $redirect ) {
			$values[] = $wpdb->prepare(
				'(%s, %s, %s, %s, %d, %s, %s)',
				$redirect->get_title(),
				$redirect->get_source(),
				$redirect->get_path(),
				wp_json_encode( $redirect->get_destination() ),
				$redirect->get_type(),
				$this->options_to_string( $redirect->get_options() ),
				wp_json_encode( $redirect->get_rules() )
			);
		}

		if ( empty( $values ) ) {
			return 0;
		}

		$values = implode( ',', $values );

		$query = "INSERT INTO {$wpdb->prefix}smartcrawl_redirects (title, source, path, destination, type, options, rules) VALUES $values;";

		return $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Updates redirect data.
	 *
	 * @param Item[] $redirects Redirect items.
	 *
	 * @return false|int
	 */
	public function update_redirects( $redirects ) {
		global $wpdb;

		$values_array = array();

		foreach ( $redirects as $redirect ) {
			if ( ! $redirect->get_id() ) {
				return false;
			}

			$values_array[] = $wpdb->prepare(
				'(%d, %s, %s, %s, %s, %d, %s, %s)',
				$redirect->get_id(),
				$redirect->get_title(),
				$redirect->get_source(),
				$redirect->get_path(),
				$redirect->get_destination(),
				$redirect->get_type(),
				$this->options_to_string( $redirect->get_options() ),
				wp_json_encode( $redirect->get_rules() )
			);
		}

		if ( empty( $values_array ) ) {
			return 0;
		}

		$values = implode( ',', $values_array );

		return $wpdb->query( "INSERT INTO {$wpdb->prefix}smartcrawl_redirects (id, title, source, path, destination, type, options, rules) VALUES $values ON DUPLICATE KEY UPDATE title = VALUES(title), source = VALUES(source), path = VALUES(path), destination = VALUES(destination), type = VALUES(type), options = VALUES(options), rules = VALUES(rules);" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Retrieves total number of redirects.
	 *
	 * @return int
	 */
	public function get_count() {
		global $wpdb;

		if ( ! $this->table_exists() ) {
			return 0;
		}

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}smartcrawl_redirects" );
	}

	/**
	 * Handles to deflate row data.
	 *
	 * @param stdClass $row Redirect item as a row.
	 *
	 * @return array
	 */
	private function map_row_to_deflated( $row ) {
		$model = $this->map_row_to_model( $row );

		return $model->deflate();
	}

	/**
	 * Handles to map row data to Item model.
	 *
	 * @param stdClass|null $row Redirect item as a row.
	 *
	 * @return Item|null
	 */
	private function map_row_to_model( $row ) {
		if ( ! $row ) {
			return null;
		}

		$destination = json_decode( $row->destination, true );

		if ( ! $destination ) {
			$destination = $row->destination;
		}

		$model = ( new Item() )
			->set_id( $row->id )
			->set_title( $row->title )
			->set_source( $row->source )
			->set_path( $row->path )
			->set_destination( $destination )
			->set_type( $row->type )
			->set_options( $this->options_to_array( $row->options ) );

		if ( GeoDB::get()->get_license() ) {
			$model->set_rules( json_decode( $row->rules, true ) );
		}

		return $model;
	}

	/**
	 * Handles to map Item model to row.
	 *
	 * @param Item $redirect Redirect item.
	 *
	 * @return array
	 */
	protected function map_model_to_row( $redirect ) {
		$destination = $redirect->get_destination();

		if ( ! empty( $destination ) ) {
			$destination = wp_json_encode( $destination );
		}

		return array(
			'title'       => $redirect->get_title(),
			'source'      => $redirect->get_source(),
			'path'        => $redirect->get_path(),
			'destination' => $destination,
			'type'        => $redirect->get_type(),
			'options'     => $this->options_to_string( $redirect->get_options() ),
			'rules'       => wp_json_encode( $redirect->get_rules() ),
		);
	}

	/**
	 * Generates WHERE clause's format which is used to update a row in DB table.
	 *
	 * @return string[]
	 */
	private function formats() {
		return array(
			'%s',
			'%s',
			'%s',
			'%s',
			'%d',
			'%s',
			'%s',
		);
	}

	/**
	 * Renders options to string.
	 *
	 * @param array $options Options as an array.
	 *
	 * @return string
	 */
	private function options_to_string( $options ) {
		if ( empty( $options ) ) {
			return '';
		}

		return implode( '|', $options );
	}

	/**
	 * Renders options string into an array.
	 *
	 * @param string $options_str Options as a string.
	 *
	 * @return array
	 */
	private function options_to_array( $options_str ) {
		if ( empty( $options_str ) ) {
			return array();
		}

		return explode( '|', $options_str );
	}
}