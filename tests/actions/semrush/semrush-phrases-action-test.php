<?php

namespace Yoast\WP\SEO\Tests\Actions\SEMrush;

use Brain\Monkey;
use Mockery;
use Yoast\WP\SEO\Actions\SEMrush\SEMrush_Phrases_Action;
use Yoast\WP\SEO\Config\SEMrush_Client;
use Yoast\WP\SEO\Tests\TestCase;

/**
 * Class SEMrush_Phrases_Action_Test
 *
 * @group semrush
 *
 * @coversDefaultClass \Yoast\WP\SEO\Actions\SEMrush\SEMrush_Phrases_Action
 */
class SEMrush_Phrases_Action_Test extends TestCase {

	/**
	 * @var SEMrush_Phrases_Action
	 */
	protected $instance;

	/**
	 * @var Mockery\MockInterface|SEMrush_Client
	 */
	protected $client_instance;

	/**
	 * @inheritDoc
	 */
	public function setUp() {
		parent::setUp();

		$this->client_instance = Mockery::mock( SEMrush_Client::class );
		$this->instance        = new SEMrush_Phrases_Action( $this->client_instance );
	}

	/**
	 * Tests if the needed attributes are set correctly.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$this->assertAttributeInstanceOf( SEMrush_Client::class, 'client', $this->instance );
	}

	/**
	 * Tests getting related keyphrases via an API call to SEMrush.
	 *
	 * @covers ::get_related_keyphrases
	 */
	public function test_get_related_keyphrases_from_api() {
		$keyphrase = 'seo';
		$database  = 'us';

		Monkey\Functions\expect( 'get_transient' )
			->times( 1 )
			->with( 'wpseo_semrush_related_keyphrases_' . $keyphrase . '_' . $database )
			->andReturn( false );

		$options = [
			'params' => [
				'phrase'         => $keyphrase,
				'database'       => $database,
				'export_columns' => 'Ph,Nq,Cp,Co,Nr,Td,Rr',
				'display_limit'  => 10,
				'display_offset' => 0,
				'display_sort'   => 'nq_desc',
				'display_filter' => '%2B|Nq|Lt|1000',
			],
		];

		$return_data = [
			'data'   => [
				'column_names' => [],
				'rows'         => [],
			],
			'status' => 200,
		];

		$this->client_instance
			->expects( 'get' )
			->with( SEMrush_Phrases_Action::KEYPHRASES_URL, $options )
			->andReturn( $return_data );

		Monkey\Functions\expect( 'set_transient' )
			->times( 1 )
			->with( 'wpseo_semrush_related_keyphrases_' . $keyphrase . '_' . $database, $return_data, \DAY_IN_SECONDS );

		$this->assertEquals(
			(object) [
				'results' => [
					'column_names' => [],
					'rows'         => [],
				],
				'status'  => 200,
			],
			$this->instance->get_related_keyphrases( $keyphrase, $database )
		);
	}

	/**
	 * Tests a valid related keyphrases retrieval from cache.
	 *
	 * @covers ::get_related_keyphrases
	 */
	public function test_get_related_keyphrases_from_cache() {
		$keyphrase = 'seo';
		$database  = 'us';

		Monkey\Functions\expect( 'get_transient' )
			->times( 1 )
			->with( 'wpseo_semrush_related_keyphrases_' . $keyphrase . '_' . $database )
			->andReturn( [
				'data'   => [
					'column_names' => [],
					'rows'         => [],
				],
				'status' => 200,
			] );

		$this->client_instance->expects( 'get' )->times( 0 );

		Monkey\Functions\expect( 'set_transient' )->times( 0 );

		$this->assertEquals(
			(object) [
				'results' => [
					'column_names' => [],
					'rows'         => [],
				],
				'status'  => 200,
			],
			$this->instance->get_related_keyphrases( $keyphrase, $database )
		);
	}
}
