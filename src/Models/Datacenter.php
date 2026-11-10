<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Models;

use Hubleto\App\Community\Settings\Models\Country;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Text;
use Hubleto\Framework\Db\Column\Varchar;

class Datacenter extends \Hubleto\Erp\Model
{
  public string $table = 'servers_datacenters';
  public string $recordManagerClass = RecordManagers\Datacenter::class;
  public ?string $lookupSqlValue = '{%TABLE%}.name';
  public ?string $lookupUrlAdd = 'server-report/datacenters/add';
  public ?string $lookupUrlDetail = 'server-report/datacenters/{%ID%}';

  public array $relations = [
    'COUNTRY' => [ self::BELONGS_TO, Country::class, 'id_country', 'id' ],
  ];

  /**
   * Describes columns of the model.
   *
   * @return array
   *
   */
  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'name' => (new Varchar($this, $this->translate('Name')))->setRequired()->setDefaultVisible(),
      'street_line_1' => (new Varchar($this, $this->translate('Street, line 1')))->setDefaultVisible(),
      'street_line_2' => (new Varchar($this, $this->translate('Street, line 2'))),
      'city' => (new Varchar($this, $this->translate('City')))->setDefaultVisible(),
      'postal_code' => (new Varchar($this, $this->translate('Postal code'))),
      'id_country' => (new Lookup($this, $this->translate('Country'), Country::class))->setFkOnDelete('SET NULL')->setDefaultVisible(),
      'access_info' => (new Text($this, $this->translate('Access information'))),
      'description' => (new Text($this, $this->translate('Description'))),
      'note' => (new Text($this, $this->translate('Note'))),
    ]);
  }

  /**
   * Describes the table (list) of records.
   *
   * @return \Hubleto\Framework\Description\Table
   *
   */
  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();

    $description->ui['title'] = '';
    $description->ui['addButtonText'] = $this->translate('Add datacenter');
    $description->show(['header', 'fulltextSearch', 'columnSearch', 'moreActionsButton']);

    return $description;
  }

}
