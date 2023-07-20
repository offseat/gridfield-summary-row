<?php

namespace Offseat\GridField;

use Offseat\Utils\FileSizeFormat;
use SilverStripe\Core\Extensible;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\FieldType\DBInt;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBFloat;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\FieldType\DBDecimal;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;

class GridFieldSummaryRow implements GridField_HTMLProvider
{
    use Extensible;
    use Configurable;

    /**
     * List of data types that will result in a sum.
     * 
     * *NOTE* This class will also check for instances of these classes 
     */
    private static array $allowed_classes = [
        DBFloat::class,
        DBDecimal::class,
        DBInt::class,
        DBMoney::class
    ];

    private $show_only;

    public function __construct($fragment = 'footer', $show_only = []) 
    {
        $this->fragment = $fragment;
        $this->show_only = $show_only;
    }

    /**
     * Is the provided field on our allowed list?
     */
    protected function isFieldAllowed(DBField $field): bool
    {
        foreach ($this->config()->allowed_classes as $class) {
            if (get_class($field) == $class || is_subclass_of($field, $class)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Is the summary field on our allowed to have summary
     *
     * @param string $column
     * @return bool
     */
    protected function hasSummary(string $column): bool
    {
        return sizeof($this->show_only) && in_array($column, $this->show_only);
    }


    /**
     * Render this row
     * 
     * @param GridField $gridField The current gridfield
     * 
     * @return array
     */
    public function getHTMLFragments($gridField) 
    {
        $columns = $gridField->getColumns();
        $list = $gridField->getList();
        $summary_values = ArrayList::create();
        $singleton = Injector::inst()->get($list->dataClass, true);
        $db = $singleton->config()->db;

        foreach ($columns as $column) {
            $field = $singleton->dbObject($column);
            $summary_value = "";

            if (empty($field) || !$this->isFieldAllowed($field) || !$this->hasSummary($column)) {
                $obj = DBText::create('Summary');
            } else {
                $obj = clone $field;
                if ($db[$column] == 'Money') {
                    $summary_value = $list->sum($column . 'Amount');
                } elseif ($column == 'FileSize') {
                    $obj = DBText::create('FileSize');
                    $summary_value = FileSizeFormat::bytes2memnicestring($list->sum($column));
                } else {
                    $summary_value = $list->sum($column);
                }
            }

            $this->extend('updateSummaryValue', $column, $summary_value);

            $obj->setValue($summary_value);

            $summary_values->push(
                ArrayData::create(["Value" => $obj])
            );
        }

        $data = ArrayData::create(['SummaryValues' => $summary_values]);

        return [$this->fragment => $data->renderWith(__CLASS__)];
    }
}