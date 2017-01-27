<?php defined("IN_GOMA") OR die();

class decimalSQLField extends intSQLField
{
    /**
     * generatesa a numeric field
     * @name formfield
     * @access public
     * @param string - title
     * @return FormField|NumberField|TextArea
     */
    public function formfield($title = null)
    {
        return new NumberField($this->name, $title);
    }

    /**
     * for db.
     */
    public function forDB() {
        return str_replace(',','.',$this->value);
    }
}