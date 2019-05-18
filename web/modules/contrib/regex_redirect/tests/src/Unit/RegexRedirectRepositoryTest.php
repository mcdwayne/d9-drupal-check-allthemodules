<?php

namespace Drupal\Tests\regex_redirect\Unit;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\regex_redirect\Entity\RegexRedirect;
use Drupal\regex_redirect\RegexRedirectRepository;
use Drupal\redirect\Exception\RedirectLoopException;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the controller for reservations.
 *
 * @group reservation
 * @group legacy
 *
 * @coversDefaultClass \Drupal\regex_redirect\RegexRedirectRepository
 */
class RegexRedirectRepositoryTest extends UnitTestCase {

  use StringTranslationTrait;

  /**
   * The mock container.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * The entity type manager ObjectProphecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManager;

  /**
   * The regex redirect entity ObjectProphecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $regexRedirect;

  /**
   * The regex redirect storage ObjectProphecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $regexRedirectStorage;

  /**
   * The url ObjectProphecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $url;

  /**
   * The statement ObjectProphecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $statement;

  /**
   * The database connection ObjectProphecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $connection;

  /**
   * The request ObjectProphecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $request;

  /**
   * The request stack ObjectProphecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->container = new ContainerBuilder();
    $this->container->set('string_translation', $this->getStringTranslationStub());

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->regexRedirect = $this->prophesize(RegexRedirect::class);
    $this->regexRedirectStorage = $this->prophesize(EntityStorageInterface::class);
    $this->url = $this->prophesize(Url::class);
    $this->statement = $this->prophesize(StatementInterface::class);
    $this->connection = $this->prophesize(Connection::class);
    $this->request = $this->prophesize(Request::class);
    $this->requestStack = $this->prophesize(RequestStack::class);

    \Drupal::setContainer($this->container);
  }

  /**
   * Retrieve 'queried' regex redirect entities.
   *
   * @param array $values
   *   The redirect data.
   *
   * @return array
   *   An array containing regex redirect objects with the necessary values.
   */
  protected static function getQueryObject(array $values) {
    $redirects = [];
    foreach ($values as $value) {
      $redirect_object = new \stdClass();
      $redirect_object->regex_redirect_source = $value['source'];
      $redirect_object->rid = $value['id'];
      $redirects[] = $redirect_object;
    }

    return $redirects;
  }

  /**
   * Data provider for test.
   */
  public function getValidRedirects() {
    return [
      [
        [
          [
            'id' => 1,
            'source' => 'article-with-an-unnecessary-long-name',
            'redirect' => '/article',
            'actual' => 'article-with-an-unnecessary-long-name',
            'expected' => '/article',

          ],
        ],
      ],
      [
        [
          [
            'id' => 2,
            'source' => 'test\/(?P<alphanumerical>[0-9a-z]+)',
            'redirect' => '/success/<alphanumerical>',
            'actual' => 'test/abc1',
            'expected' => '/success/abc1',
          ],
        ],
      ],
      [
        [
          [
            'id' => 3,
            'source' => 'chapter\/(?P<chapternr>[0-9\.]+)\/page\/(?P<pagenr>[0-9\.]+)\/paragraph\/(?P<paragraph>[0-9a-z]+)',
            'redirect' => '/<chapternr>/<pagenr>/<paragraph>',
            'actual' => 'chapter/3/page/623/paragraph/4b',
            'expected' => '/3/623/4b',
          ],
        ],
      ],
      [
        [
          [
            'id' => 4,
            'source' => 'characters\/(?P<char>[0-9a-z#$%^&*()+=-]+)',
            'redirect' => '/c/<char>',
            'actual' => 'characters/#$%^asdf&*()+q234=-',
            'expected' => '/c/#$%^asdf&*()+q234=-',
          ],
        ],
      ],
    ];
  }

  /**
   * @covers ::findMatchingRedirect
   * @dataProvider getValidRedirects
   *
   * @throws \Drupal\redirect\Exception\RedirectLoopException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function testWithMatchingRedirect($data) {
    $query_object = $this->getQueryObject($data);

    $this->url->toUriString()->willReturn($data[0]['redirect'])->shouldBeCalled();
    $this->url->toString()->willReturn($data[0]['redirect'])->shouldBeCalled();
    $this->regexRedirect->getRedirectUrl()->willReturn($this->url->reveal())->shouldBeCalled();
    $this->regexRedirect->setRedirect($data[0]['expected'])->shouldBeCalled();
    $this->regexRedirectStorage->load(Argument::any())->willReturn($this->regexRedirect->reveal())->shouldBeCalled();
    $this->entityTypeManager->getStorage('regex_redirect')->willReturn($this->regexRedirectStorage->reveal())->shouldBeCalled();
    $this->statement->fetchAll()->willReturn($query_object)->shouldBeCalled();
    $this->connection->query(Argument::any())->willReturn($this->statement->reveal())->shouldBeCalled();
    $this->request->getBaseUrl()->willReturn(Argument::any())->shouldBeCalled();
    $this->requestStack->getCurrentRequest()->willReturn($this->request->reveal())->shouldBeCalled();

    $regex_redirect_repository = new RegexRedirectRepository($this->entityTypeManager->reveal(), $this->connection->reveal(), $this->requestStack->reveal());
    $result = $regex_redirect_repository->findMatchingRedirect($data[0]['actual']);
    $this->assertTrue($result instanceof EntityInterface);
  }

  /**
   * Data provider for test.
   */
  public function getValidRecursiveRedirects() {
    return [
      [
        [
          [
            'id' => 1,
            'source' => 'first-page',
            'redirect' => 'base.org/second-page',
            'actual' => 'first-page',
            'expected' => 'base.org/second-page',
          ],
          [
            'id' => 2,
            'source' => 'second-page',
            'redirect' => 'base.org/third-page',
            'actual' => 'second-page',
            'expected' => 'base.org/third-page',
          ],
        ],
      ],
      [
        [
          [
            'id' => 1,
            'source' => 'test\/(?P<alphanumerical>[0-9a-z]+)',
            'redirect' => 'base.org/success/<alphanumerical>',
            'actual' => 'test/abc1',
            'expected' => 'base.org/success/abc1',
          ],
          [
            'id' => 2,
            'source' => '^success\/(?P<alphanumerical>[0-9a-z]+)$',
            'redirect' => 'base.org/another-success/<alphanumerical>',
            'actual' => 'success/abc1',
            'expected' => 'base.org/another-success/abc1',
          ],
        ],
      ],
      [
        [
          [
            'id' => 1,
            'source' => 'chapter\/(?P<chapternr>[0-9\.]+)\/page\/(?P<pagenr>[0-9\.]+)\/paragraph\/(?P<paragraph>[0-9a-z]+)',
            'redirect' => 'base.org/book/<chapternr>/<pagenr>/<paragraph>',
            'actual' => 'chapter/3/page/623/paragraph/4b',
            'expected' => 'base.org/book/3/623/4b',
          ],
          [
            'id' => 2,
            'source' => '^book\/(?P<chapternr>[0-9\.]+)\/(?P<pagenr>[0-9\.]+)\/(?P<paragraph>[0-9a-z]+)$',
            'redirect' => 'base.org/<chapternr>-<pagenr>-<paragraph>',
            'actual' => 'book/3/623/4b',
            'expected' => 'base.org/3-623-4b',
          ],
        ],
      ],
    ];
  }

  /**
   * @covers ::findMatchingRedirect
   * @dataProvider getValidRecursiveRedirects
   *
   * @throws \Drupal\redirect\Exception\RedirectLoopException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function testWithMatchingRecurrentRedirects($data) {
    $query_object = $this->getQueryObject($data);

    $this->url->toUriString()->willReturn($data[0]['redirect'], $data[1]['redirect'])->shouldBeCalled();
    $this->url->toString()->willReturn($data[0]['redirect'], $data[1]['redirect'])->shouldBeCalled();
    $this->regexRedirect->getRedirectUrl()->willReturn($this->url->reveal())->shouldBeCalled();
    $this->regexRedirect->setRedirect($data[0]['expected'])->shouldBeCalled();
    $this->regexRedirect->setRedirect($data[1]['expected'])->shouldBeCalled();
    $this->regexRedirectStorage->load(Argument::any())->willReturn($this->regexRedirect->reveal())->shouldBeCalled();
    $this->entityTypeManager->getStorage('regex_redirect')->willReturn($this->regexRedirectStorage->reveal())->shouldBeCalled();
    $this->statement->fetchAll()->willReturn($query_object)->shouldBeCalled();
    $this->connection->query(Argument::any())->willReturn($this->statement->reveal())->shouldBeCalled();
    $this->request->getBaseUrl()->willReturn('base.org')->shouldBeCalled();
    $this->requestStack->getCurrentRequest()->willReturn($this->request->reveal())->shouldBeCalled();

    $regex_redirect_repository = new RegexRedirectRepository($this->entityTypeManager->reveal(), $this->connection->reveal(), $this->requestStack->reveal());
    $result = $regex_redirect_repository->findMatchingRedirect($data[0]['actual']);
    $this->assertTrue($result instanceof EntityInterface);
  }

  /**
   * Data provider for test.
   */
  public function getNonRedirects() {
    return [
      [
        [
          [
            'actual' => 'admin/page',
          ],
        ],
      ],
      [
        [
          [
            'actual' => 'node/type/1',
          ],
        ],
      ],
    ];
  }

  /**
   * @covers ::findMatchingRedirect
   * @dataProvider getNonRedirects
   *
   * @throws \Drupal\redirect\Exception\RedirectLoopException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function testForNonRequiredRedirect($data) {
    $this->connection->query(Argument::any())->shouldNotBeCalled();

    $regex_redirect_repository = new RegexRedirectRepository($this->entityTypeManager->reveal(), $this->connection->reveal(), $this->requestStack->reveal());
    $result = $regex_redirect_repository->findMatchingRedirect($data[0]['actual']);
    $this->assertTrue($result === NULL);
  }

  /**
   * @covers ::findMatchingRedirect
   *
   * @throws \Drupal\redirect\Exception\RedirectLoopException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function testForNoRedirects() {
    $this->entityTypeManager->getStorage('regex_redirect')->shouldNotBeCalled();
    $this->statement->fetchAll()->willReturn(NULL)->shouldBeCalled();
    $this->connection->query(Argument::any())->willReturn($this->statement->reveal())->shouldBeCalled();

    $regex_redirect_repository = new RegexRedirectRepository($this->entityTypeManager->reveal(), $this->connection->reveal(), $this->requestStack->reveal());
    $result = $regex_redirect_repository->findMatchingRedirect('search/page');
    $this->assertTrue($result === NULL);
  }

  /**
   * Data provider for test.
   */
  public function getRedirectsWithoutMatches() {
    return [
      [
        [
          [
            'id' => 1,
            'source' => 'article-with-an-unnecessary-long-name',
            'redirect' => '/article',
            'actual' => 'article-with-but-not-quite-the-same-name',
            'expected' => '/article',

          ],
        ],
      ],
      [
        [
          [
            'id' => 2,
            'source' => 'test\/(?P<alphanumerical>[0-9a-z]+)',
            'redirect' => '/success/<alphanumerical>',
            'actual' => 'testing/abc1',
            'expected' => '/success/abc1',
          ],
        ],
      ],
    ];
  }

  /**
   * @covers ::findMatchingRedirect
   * @dataProvider getRedirectsWithoutMatches
   *
   * @throws \Drupal\redirect\Exception\RedirectLoopException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function testWithNoMatching($data) {
    $query_object = $this->getQueryObject($data);

    $this->entityTypeManager->getStorage('regex_redirect')->shouldNotBeCalled();
    $this->statement->fetchAll()->willReturn($query_object)->shouldBeCalled();
    $this->connection->query(Argument::any())->willReturn($this->statement->reveal())->shouldBeCalled();

    $regex_redirect_repository = new RegexRedirectRepository($this->entityTypeManager->reveal(), $this->connection->reveal(), $this->requestStack->reveal());
    $result = $regex_redirect_repository->findMatchingRedirect($data[0]['actual']);
    $this->assertTrue($result === NULL);
  }

  /**
   * Data provider for test.
   */
  public function getRedirectLoopData() {
    return [
      [
        [
          [
            'id' => 1,
            'source' => 'page',
            'redirect' => '/page',
            'actual' => 'page',
            'expected' => '/page',
          ],
        ],
      ],
      [
        [
          [
            'id' => 1,
            'source' => 'initial-page',
            'redirect' => '/another-page',
            'actual' => 'initial-page',
            'expected' => '/another-page',
          ],
          [
            'id' => 2,
            'source' => 'another-page',
            'redirect' => '/initial-page',
            'actual' => 'another-page',
            'expected' => '/initial-page',
          ],
        ],
      ],
      [
        [
          [
            'id' => 1,
            'source' => 'initial-page',
            'redirect' => '/another-page',
            'actual' => 'initial-page',
            'expected' => '/another-page',
          ],
          [
            'id' => 2,
            'source' => 'another-page',
            'redirect' => '/yet-another-page',
            'actual' => 'another-page',
            'expected' => '/yet-another-page',
          ],
          [
            'id' => 3,
            'source' => 'yet-another-page',
            'redirect' => '/initial-page',
            'actual' => 'yet-another-page',
            'expected' => '/initial-page',
          ],
        ],
      ],
    ];
  }

  /**
   * @covers ::findMatchingRedirect
   * @dataProvider getRedirectLoopData
   *
   * @throws \Drupal\redirect\Exception\RedirectLoopException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *
   * @expectedDeprecation SafeMarkup::format() is scheduled for removal in Drupal 9.0.0. Use \Drupal\Component\Render\FormattableMarkup. See https://www.drupal.org/node/2549395.
   */
  public function testForRedirectLoop($data) {
    $query_object = $this->getQueryObject($data);

    $this->url->toUriString()->willReturn($data[0]['redirect'])->shouldBeCalled();
    $this->url->toString()->willReturn($data[0]['redirect'])->shouldBeCalled();
    $this->regexRedirect->getRedirectUrl()->willReturn($this->url->reveal())->shouldBeCalled();
    $this->regexRedirect->setRedirect($data[0]['expected'])->shouldBeCalled();
    $this->regexRedirect->getSourceUrl()->willReturn($data[0]['source']);
    $this->regexRedirect->id()->willReturn($data[0]['id']);
    $this->regexRedirectStorage->load(Argument::any())->willReturn($this->regexRedirect->reveal())->shouldBeCalled();
    $this->entityTypeManager->getStorage('regex_redirect')->willReturn($this->regexRedirectStorage->reveal())->shouldBeCalled();
    $this->statement->fetchAll()->willReturn($query_object)->shouldBeCalled();
    $this->connection->query(Argument::any())->willReturn($this->statement->reveal())->shouldBeCalled();
    $this->request->getBaseUrl()->willReturn(Argument::any())->shouldBeCalled();
    $this->requestStack->getCurrentRequest()->willReturn($this->request->reveal())->shouldBeCalled();

    $regex_redirect_repository = new RegexRedirectRepository($this->entityTypeManager->reveal(), $this->connection->reveal(), $this->requestStack->reveal());
    $this->setExpectedException(RedirectLoopException::class);
    $regex_redirect_repository->findMatchingRedirect($data[0]['actual']);
  }

  /**
   * Data provider for test.
   */
  public function getSourcePath() {
    return [
      [
        [
          'ids' => [1],
          'source' => 'page',
        ],
      ],
      [
        [
          'ids' => [2, 3, 5],
          'source' => 'another-page',
        ],
      ],
    ];
  }

  /**
   * @covers ::findBySourcePath
   * @dataProvider getSourcePath
   *
   * @throws \Drupal\redirect\Exception\RedirectLoopException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function testRetrievingRedirectsBySourcePath($data) {
    $query = $this->prophesize(QueryInterface::class);
    $conditioned_query = $this->prophesize(QueryInterface::class);

    $conditioned_query->execute()->willReturn($data['ids']);
    $query->condition('regex_redirect_source.path', $data['source'], 'LIKE')->willReturn($conditioned_query->reveal());
    $this->regexRedirectStorage->loadMultiple(Argument::any())->willReturn($this->regexRedirect->reveal())->shouldBeCalled();
    $this->regexRedirectStorage->getQuery()->willReturn($query->reveal())->shouldBeCalled();
    $this->entityTypeManager->getStorage('regex_redirect')->willReturn($this->regexRedirectStorage->reveal())->shouldBeCalled();

    $regex_redirect_repository = new RegexRedirectRepository($this->entityTypeManager->reveal(), $this->connection->reveal(), $this->requestStack->reveal());
    $result = $regex_redirect_repository->findBySourcePath($data['source']);
    $this->assertInstanceOf(RegexRedirect::class, $result);
  }

  /**
   * @covers ::findBySourcePath
   *
   * @throws \Drupal\redirect\Exception\RedirectLoopException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function testRetrievingRedirectsBySourcePathWithNoResults() {
    $query = $this->prophesize(QueryInterface::class);
    $conditioned_query = $this->prophesize(QueryInterface::class);

    $conditioned_query->execute()->willReturn([]);
    $query->condition('regex_redirect_source.path', 'page', 'LIKE')->willReturn($conditioned_query->reveal());
    $this->regexRedirectStorage->loadMultiple(Argument::any())->shouldNotBeCalled();
    $this->regexRedirectStorage->getQuery()->willReturn($query->reveal())->shouldBeCalled();
    $this->entityTypeManager->getStorage('regex_redirect')->willReturn($this->regexRedirectStorage->reveal())->shouldBeCalled();

    $regex_redirect_repository = new RegexRedirectRepository($this->entityTypeManager->reveal(), $this->connection->reveal(), $this->requestStack->reveal());
    $result = $regex_redirect_repository->findBySourcePath('page');
    $this->assertTrue($result === NULL);
  }

}
