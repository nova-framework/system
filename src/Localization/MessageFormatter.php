<?php

namespace Nova\Localization;

use LogicException;


class MessageFormatter
{

    /**
     * Formats a message via [ICU message format](http://userguide.icu-project.org/formatparse/messages)
     *
     * If PHP INTL is not installed a fallback will be used that supports a subset of the ICU message format.
     *
     * @param string $pattern
     * @param array $params
     * @param string $language
     * @return string
     */
    public function format($pattern, array $parameters, $language)
    {
        if (empty($parameters)) {
            return $pattern;
        }

        if (! class_exists('MessageFormatter', false)) {
            return $this->fallbackFormat($pattern, $parameters, $language);
        }

        $formatter = new \MessageFormatter($language, $pattern);

        return $formatter->format($parameters);
    }

    /**
     * Fallback implementation for MessageFormatter::formatMessage
     *
     * @param string $pattern
     * @param array $parameters
     * @param string $locale
     * @return string
     */
    protected function fallbackFormat($pattern, $parameters, $locale)
    {
        $tokens = static::tokenizePattern($pattern);

        foreach ($tokens as $key => $token) {
            if (! is_array($token)) {
                continue;
            }

            if (($tokens[$key] = $this->parseToken($token, $parameters, $locale)) === false) {
                throw new LogicException('Message pattern is invalid.');
            }
        }

        return implode('', $tokens);
    }

    /**
     * Parses a token
     *
     * @param array $token
     * @param array $parameters
     * @param string $locale
     * @return string
     * @throws \LogicException
     */
    private function parseToken($token, $parameters, $locale)
    {
        $key = trim($token[0]);

        if (isset($parameters[$key])) {
            $parameter = $parameters[$key];
        } else {
            return '{' . implode(',', $token) . '}';
        }

        $type = isset($token[1]) ? trim($token[1]) : 'none';

        switch ($type) {
            case 'date':
            case 'time':
            case 'spellout':
            case 'ordinal':
            case 'duration':
            case 'choice':
            case 'selectordinal':
                throw new LogicException("Message format '$type' is not supported.");

            case 'number':
                if (is_integer($parameter) && (! isset($token[2]) || (trim($token[2]) == 'integer'))) {
                    return $parameter;
                }

                throw new LogicException("Message format 'number' is only supported for integer values.");

            case 'none':
                return $parameter;
        };

        if (! isset($token[2])) {
            return false;
        }

        $message = false;

        $tokens = static::tokenizePattern($token[2]);

        $count = count($tokens);

        // Process for the "select" type.
        if ($type == 'select') {
            for ($i = 0; ($i + 1) < $count; $i++) {
                if (is_array($tokens[$i]) || ! is_array($tokens[$i + 1])) {
                    return false;
                }

                $selector = trim($tokens[$i]);

                $i++;

                if ((($message === false) && ($selector == 'other')) || ($selector == $parameter)) {
                    $message = implode(',', $tokens[$i]);
                }
            }
        }

        // Process for the "plural" type.
        else if ($type == 'plural') {
            $offset = 0;

            for ($i = 0; ($i + 1) < $count; $i++) {
                if (is_array($tokens[$i]) || ! is_array($tokens[$i + 1])) {
                    return false;
                }

                $selector = trim($tokens[$i]);

                $i++;

                if (($i == 1) && (strncmp($selector, 'offset:', 7) === 0)) {
                    $pos = mb_strpos(str_replace(array("\n", "\r", "\t"), ' ', $selector), ' ', 7);

                    $offset = (int) trim(mb_substr($selector, 7, $pos - 7));

                    $selector = trim(mb_substr($selector, $pos + 1));
                }

                if ((($message === false) && ($selector == 'other'))
                    || (($selector[0] == '=') && ((int) mb_substr($selector, 1) == $parameter))
                    || (($selector == 'one') && (($parameter - $offset) == 1))) {
                    $message = implode(',', str_replace('#', $parameter - $offset, $tokens[$i]));
                }
            }
        }

        if ($message !== false) {
            return $this->fallbackFormat($message, $parameters, $locale);
        }

        return false;
    }


    /**
     * Tokenizes a pattern by separating normal text from replaceable patterns
     *
     * @param string $pattern patter to tokenize
     * @return array array of tokens
     * @throws \LogicException when unsupported formatting is used.
     */
    private static function tokenizePattern($pattern)
    {
        $depth = 1;

        //
        $start = $pos = mb_strpos($pattern, '{');

        if ($pos === false) {
            return array($pattern);
        }

        $tokens = array(
            mb_substr($pattern, 0, $pos)
        );

        while (true) {
            $open  = mb_strpos($pattern, '{', $pos + 1);
            $close = mb_strpos($pattern, '}', $pos + 1);

            if ($open === false) {
                if ($close === false) {
                    break;
                }

                $open = mb_strlen($pattern);
            }

            if ($close > $open) {
                $depth++;

                $pos = $open;
            } else {
                $depth--;

                $pos = $close;
            }

            if ($depth == 0) {
                $tokens[] = explode(',', mb_substr($pattern, $start + 1, $pos - $start - 1), 3);

                $start = $pos + 1;

                $tokens[] = mb_substr($pattern, $start, $open - $start);

                $start = $open;
            }
        }

        if ($depth == 0) {
            return $tokens;
        }

        throw new LogicException('Message pattern is invalid.');
    }
}
