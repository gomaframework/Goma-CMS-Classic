<?php
defined("IN_GOMA") OR die();

/**
 * Includes information how to treat this request.
 *
 * @package Goma
 *
 * @author Goma-Team
 * @copyright 2016 Goma-Team
 * @license GNU Lesser General Public License, version 3; see "LICENSE.txt"
 *
 * @version 1.0
 */
class OutputException extends LogicException
{
    /**
     * stores output, that should not be there.
     *
     * @var string
     */
    protected $output;

    /**
     * OutputException constructor.
     * @param string $message
     * @param null $output
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $output = null, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->output = $output;
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param string $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * @return string
     */
    public function getDeveloperMessage() {
        return $this->output;
    }
}
