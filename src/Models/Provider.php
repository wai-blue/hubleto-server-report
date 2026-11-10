<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Models;

use Hubleto\App\Community\Settings\Models\Country;
use Hubleto\Framework\Db\Column\Email;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Text;
use Hubleto\Framework\Db\Column\Varchar;

class Provider extends \Hubleto\Erp\Model
{
  public string $table = 'servers_providers';
  public string $recordManagerClass = RecordManagers\Provider::class;
  public ?string $lookupSqlValue = '{%TABLE%}.name';
  public ?string $lookupUrlAdd = 'server-report/providers/add';
  public ?string $lookupUrlDetail = 'server-report/providers/{%ID%}';

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
      'contact_person' => (new Varchar($this, $this->translate('Contact person')))->setDefaultVisible(),
      'email' => (new Email($this, $this->translate('E-mail')))->setDefaultVisible(),
      'phone' => (new Varchar($this, $this->translate('Phone')))->setDefaultVisible(),
      'website' => (new Varchar($this, $this->translate('Website')))->setReactComponent('InputHyperlink'),
      'street_line_1' => (new Varchar($this, $this->translate('Street, line 1'))),
      'street_line_2' => (new Varchar($this, $this->translate('Street, line 2'))),
      'city' => (new Varchar($this, $this->translate('City')))->setDefaultVisible(),
      'postal_code' => (new Varchar($this, $this->translate('Postal code'))),
      'id_country' => (new Lookup($this, $this->translate('Country'), Country::class))->setFkOnDelete('SET NULL')->setDefaultVisible(),
      'invoicing_name' => (new Varchar($this, $this->translate('Invoicing name'))),
      'invoicing_address' => (new Text($this, $this->translate('Invoicing address'))),
      'tax_id' => (new Varchar($this, $this->translate('Tax ID'))),
      'vat_id' => (new Varchar($this, $this->translate('VAT ID'))),
      'invoicing_email' => (new Email($this, $this->translate('Invoicing e-mail'))),
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
    $description->ui['addButtonText'] = $this->translate('Add provider');
    $description->show(['header', 'fulltextSearch', 'columnSearch', 'moreActionsButton']);

    return $description;
  }

}
