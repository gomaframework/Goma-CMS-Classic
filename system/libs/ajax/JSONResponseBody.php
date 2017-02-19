<?php
defined("IN_GOMA") OR die();

/**
 * JSON Response.
 *
 * @package Goma
 *
 * @author Goma-Team
 * @copyright 2016 Goma-team
 *
 * @version 1.0
 *
 * @property IRestResponse body
 */
class JSONResponseBody extends GomaResponseBody
{
    /**
     * JSONResponseBody constructor.
     * @param null|string $body
     */
    public function __construct($body)
    {
        parent::__construct($body);

        $this->isFullPage = true;
    }

    /**
     * @param GomaResponse $response
     * @return string
     */
    public function toServableBody($response)
    {
        $this->callExtending("beforeServe", $response);

        $response->setHeader("content-type", "text/json");

        if(is_a($this->body, "IRestResponse")) {
            return json_encode($this->body->ToRestArray());
        }

        return json_encode($this->body);
    }

    /**
     * this is required to allow arrays.
     *
     * @param string $body
     * @return $this
     */
    public function setBody($body)
    {
        if(is_string($body)) {
            $data = json_decode($body);
            if($data !== null) {
                $this->body = $data;
                return $this;
            }
        }

        $this->body = $body;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if(is_a($this->body, "IRestResponse")) {
            return json_encode($this->body->ToRestArray());
        }

        return json_encode($this->body);
    }
}
