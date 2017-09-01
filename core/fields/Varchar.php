<?php defined("IN_GOMA") OR die();
/**
 * Every value of an field can used as object if you call doObject($offset) for varchar-fields
 * This Object has some very cool methods to convert the field
 */
class Varchar extends DBField
{
    /**
     * strips all tags of the value
     */
    public function strtiptags()
    {
        return strip_tags($this->value);
    }

    /**
     * makes a substring of this value
     */
    public function substr($start, $length = null)
    {
        if($length === null)
        {
            return substr($this->value, $start);
        } else
        {
            return substr($this->value, $start, $length);
        }
    }
    /**
     * this returns the length of the string
     */
    public function length()
    {
        return strlen($this->value);
    }

    /**
     * generates a special dynamic form-field
     */
    public function formfield($title = null)
    {

        if(strpos($this->value, "\n"))
        {
            return new TextArea($this->name, $title);
        } else
        {
            return parent::formfield($title);
        }
    }

    /**
     * renders text as BBcode
     */
    public function bbcode()
    {
        $text = new Text($this->value);
        return $text->bbcode();
    }

    /**
     * converts this with date
     * @return string
     */
    public function date($format = DATE_FORMAT)
    {
        return goma_date($format, $this->value);
    }

    /**
     * for template
     *
     * @return string
     */
    public function forTemplate() {
        return $this->text();
    }
}
