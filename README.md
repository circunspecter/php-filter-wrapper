# GrindNous\Filter

PHP Filter wrapper. Class which facilitates the use of filters in batches when validate or sanitize data.

Features

- Simplifying the use of extension
- Grouping of filters
- Associated error messages

Requirements

- PHP 5.2+

## Public Methods
---
    /**
     * @param array $data Field name and value pairs.
     * @return object
     */
    static factory(array $data)
---
    /**
     * @param mixed $fields string|array with field(s) that apply the filter; if TRUE, the filter will be applied to all given fields.
     * @param callable|int $filter Callback function or {@link http://www.php.net/manual/en/filter.filters.php filter} to apply.
     * @param mixed $options Filter options; {@link http://www.php.net/manual/en/function.filter-var.php filter_var}.
     * @return object
     */
    sanitize($fields, $filter, $options = NULL)
---
    /**
     * @param mixed $fields string|array with field(s) that apply the filter; if TRUE, the filter will be applied to all given fields.
     * @param mixed $filter Callback function, int {@link http://www.php.net/manual/en/filter.filters.php filter} or class filter to apply.
     * @param mixed $options Filter options; {@link http://www.php.net/manual/en/function.filter-var.php filter_var}.
     * @param string|array $error Error message or indexed array containing an error message string with ":field" keyword, followed by an associative array containing the field names and his human-readable versions.
     * @return object
     */
    validate($fields, $filter, $options = NULL, $error = NULL)
---
    /**
     * Indicates if all the filters have been applied correctly.
     *
     * @return bool
     */
    ok()
---
    /**
     * @return array Field name and error pairs.
     */
    errors()
---
    /**
     * @param array $data Set data and reset class.
     * @return mixed Filter object if data given, otherwise, field name and filtered value pairs or FALSE if empty.
     */
    data(array $data = array())

## Class filters
---
    not_empty
    validate('field', 'not_empty', NULL, 'Field cannot be empty.')
---
    min_length
    validate('field', 'min_length', 3, 'Field must have more than 3 characters.')
---
    max_length
    validate('field', 'max_length', 140, 'Field exceeds the limit of 140 characters.')
---
    exact_length
    validate('field', 'exact_length', 15, 'Field must be exactly 15 characters long.')
---
    valid_email
    validate('field', 'valid_email', NULL, 'You must enter a valid email address.')

## Properties
---
    multipleFieldErrors
    Indicates whether or not to continue applying the filters over a field when the first one fails. Default FALSE.
    
    \GrindNous\Filter::$multipleFieldErrors = TRUE;

## Examples
---

**One:**

    require 'GrindNous/Filter.php';
    
    $data = array(
        'username' => 'Fulanito',
        'comment' => ''
    );
    
    $validation = \GrindNous\Filter::factory($data)
                ->validate(
                    array('username', 'comment'),
                    'not_empty',
                    NULL,
                    array(':field cannot be empty.', array('username' => 'User name', 'comment' => 'Comment'))
                )
                ->validate(
                    'username',
                    FILTER_VALIDATE_REGEXP,
                    "/^[-\pL\pN\pZs_.]{5,20}$/uD",
                    'The user name doesnt match with the required format.'
                )
                ->validate('comment', 'max_length', 140, 'Comment too long.')
                ->sanitize(TRUE, 'trim');
    
    if($validation->ok())
    {
        $data = $validation->data();
    }
    else
    {
        $errors = $validation->errors();
    }
---

**Two:**

    // MyClass.php
    class MyClass
    {
        public function checkMail($email)
        {
            // Check for duplicate email
            return TRUE/FALSE;
        }
    }
---
    require 'GrindNous/Filter.php';
    require 'MyClass.php';
    
    $data = array(
        'username' => 'Menganito',
        'password' => '',
        'email' => 'menganito@example.com',
        'birthday' => '1970AA01'
    );
    
    $validation = \GrindNous\Filter::factory($data)
                ->validate(
                    array('username', 'password', 'email'),
                    'not_empty',
                    NULL,
                    array(':field cannot be empty.', array('username' => 'User name', 'password' => 'The password', 'email' => 'Email')))
                ->validate(
                    'username',
                    FILTER_VALIDATE_REGEXP,
                    "/^[-\pL\pN\pZs_.]{5,20}$/uD",
                    'The user name doesnt match with the required format.')
                ->validate('username', function() use ($data) {
                    // Check for duplicate username
                    return TRUE/FALSE;
                }, NULL, 'User name already in use.')
                ->validate('email', 'valid_email', NULL, 'You must indicate a valid email account.')
                ->validate('email', 'max_length', 60, 'Email too long.')
                ->validate('email', array('MyClass', 'checkMail'), NULL, 'This email is already in use.')
                ->validate('birthday', FILTER_VALIDATE_INT, NULL, 'Birthday must be an integer.')
                ->sanitize(TRUE, 'trim');
    
    if($validation->ok())
    {
        $data = $validation->data();
    }
    else
    {
        $errors = $validation->errors();
    }

---

- Filter extension documentation: http://www.php.net/manual/en/book.filter.php
- **FILTER_SANITIZE_FULL_SPECIAL_CHARS** is available from PHP 5.3.3 (http://www.php.net/manual/es/filter.constants.php#101547)
