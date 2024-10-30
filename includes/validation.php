<?php

class CFREDUX_Validation implements ArrayAccess
{
    private $invalid_fields = array();
    private $container = array();

    public function __construct()
    {
        $this->container = array(
        'valid' => true,
        'reason' => array(),
        'idref' => array(),
        );
    }

    public function invalidate($context, $message)
    {
        if ($context instanceof CFREDUX_FormTag) {
            $tag = $context;
        } elseif (is_array($context)) {
            $tag = new CFREDUX_FormTag($context);
        } elseif (is_string($context)) {
            $tags = cfredux_scan_form_tags(array('name' => trim($context)));
            $tag = $tags ? new CFREDUX_FormTag($tags[0]) : null;
        }

        $name = ! empty($tag) ? $tag->name : null;

        if (empty($name) || ! cfredux_is_name($name)) {
            return;
        }

        if ($this->is_valid($name)) {
            $id = $tag->get_id_option();

            if (empty($id) || ! cfredux_is_name($id)) {
                $id = null;
            }

            $this->invalid_fields[$name] = array(
            'reason' => (string) $message,
            'idref' => $id,
            );
        }
    }

    public function is_valid($name = null)
    {
        if (! empty($name)) {
            return ! isset($this->invalid_fields[$name]);
        } else {
            return empty($this->invalid_fields);
        }
    }

    public function get_invalid_fields()
    {
        return $this->invalid_fields;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (isset($this->container[$offset])) {
            $this->container[$offset] = $value;
        }

        if ('reason' == $offset && is_array($value)) {
            foreach ($value as $k => $v) {
                $this->invalidate($k, $v);
            }
        }
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (isset($this->container[$offset])) {
            return $this->container[$offset];
        }
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->container[$offset]);
    }

    public function offsetUnset(mixed $offset): void
    {
    }
}
