<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */

namespace Twilio\Rest\Voice\V1\DialingPermissions;

use Twilio\Exceptions\TwilioException;
use Twilio\InstanceContext;
use Twilio\Rest\Voice\V1\DialingPermissions\Country\HighriskSpecialPrefixList;
use Twilio\Values;
use Twilio\Version;

/**
 * PLEASE NOTE that this class contains preview products that are subject to change. Use them with caution. If you currently do not have developer preview access, please contact help@twilio.com.
 * 
 * @property \Twilio\Rest\Voice\V1\DialingPermissions\Country\HighriskSpecialPrefixList highriskSpecialPrefixes
 */
class CountryContext extends InstanceContext {
    protected $_highriskSpecialPrefixes = null;

    /**
     * Initialize the CountryContext
     * 
     * @param \Twilio\Version $version Version that contains the resource
     * @param string $isoCode The ISO country code
     * @return \Twilio\Rest\Voice\V1\DialingPermissions\CountryContext 
     */
    public function __construct(Version $version, $isoCode) {
        parent::__construct($version);

        // Path Solution
        $this->solution = array('isoCode' => $isoCode, );

        $this->uri = '/DialingPermissions/Countries/' . rawurlencode($isoCode) . '';
    }

    /**
     * Fetch a CountryInstance
     * 
     * @return CountryInstance Fetched CountryInstance
     * @throws TwilioException When an HTTP error occurs.
     */
    public function fetch() {
        $params = Values::of(array());

        $payload = $this->version->fetch(
            'GET',
            $this->uri,
            $params
        );

        return new CountryInstance($this->version, $payload, $this->solution['isoCode']);
    }

    /**
     * Access the highriskSpecialPrefixes
     * 
     * @return \Twilio\Rest\Voice\V1\DialingPermissions\Country\HighriskSpecialPrefixList 
     */
    protected function getHighriskSpecialPrefixes() {
        if (!$this->_highriskSpecialPrefixes) {
            $this->_highriskSpecialPrefixes = new HighriskSpecialPrefixList(
                $this->version,
                $this->solution['isoCode']
            );
        }

        return $this->_highriskSpecialPrefixes;
    }

    /**
     * Magic getter to lazy load subresources
     * 
     * @param string $name Subresource to return
     * @return \Twilio\ListResource The requested subresource
     * @throws \Twilio\Exceptions\TwilioException For unknown subresources
     */
    public function __get($name) {
        if (property_exists($this, '_' . $name)) {
            $method = 'get' . ucfirst($name);
            return $this->$method();
        }

        throw new TwilioException('Unknown subresource ' . $name);
    }

    /**
     * Magic caller to get resource contexts
     * 
     * @param string $name Resource to return
     * @param array $arguments Context parameters
     * @return \Twilio\InstanceContext The requested resource context
     * @throws \Twilio\Exceptions\TwilioException For unknown resource
     */
    public function __call($name, $arguments) {
        $property = $this->$name;
        if (method_exists($property, 'getContext')) {
            return call_user_func_array(array($property, 'getContext'), $arguments);
        }

        throw new TwilioException('Resource does not have a context');
    }

    /**
     * Provide a friendly representation
     * 
     * @return string Machine friendly representation
     */
    public function __toString() {
        $context = array();
        foreach ($this->solution as $key => $value) {
            $context[] = "$key=$value";
        }
        return '[Twilio.Voice.V1.CountryContext ' . implode(' ', $context) . ']';
    }
}