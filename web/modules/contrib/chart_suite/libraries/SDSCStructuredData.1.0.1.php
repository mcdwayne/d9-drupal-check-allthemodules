<?php
// PHP class library.
//   Built from '/Volumes/Travel/Projects/SeedMe/SeedMe 2/Development/APIs/StructuredData/StructuredData/srcapi'.
//   By 'mkSinglePhp'.
//   On Mon, 24 Sep 2018 18:33:40 +0000


/**
 * @file
 * The base class for specific structured data classes.
 */

namespace SDSC\StructuredData;





/**
 * @class AbstractData
 * AbstractData is an abstract base class that provides core functionality
 * used by multiple container classes, including Table, Tree, and Graph.
 *
 * #### Data attributes
 * Data objects have an associative array of attributes that
 * provide descriptive metadata for the data content.  Applications may
 * add any number and type of attributes, but this class, and others in
 * this package, recognize a few well-known attributes:
 *
 *  - 'name' (string) is a brief name of the data
 *  - 'longName' (string) is a longer more human-friendly name of the data
 *  - 'description' (string) is a block of text describing the data
 *  - 'sourceFileName' (string) is the name of the source file for the data
 *  - 'sourceMIMEType' (string) is the source file mime type
 *  - 'sourceSchemaName' (string) is the name of a source file schema
 *  - 'sourceSyntax' (string) is the source file base syntax
 *
 * All attributes are optional.
 *
 * The 'name' may be an abbreviation or acronym, while the 'longName' may
 * spell out the abbreviation or acronym.
 *
 * The 'description' may be a block of text containing several unformatted
 * sentences describing the data.
 *
 * When the data originates from a source file, the 'sourceFileName' may
 * be the name of the file. If that file's syntax does not provide a name
 * for the data, the file's name, without extensions, may be used to set
 * the name.
 *
 * In addition to the source file name, the file's MIME type may be set
 * in 'sourceMIMEType' (e.g. 'application/json'), and the equivalent file
 * syntax in 'sourceSyntax' e.g. 'json'). If the source file uses a specific
 * schema, the name of that schema is in 'sourceSchemaName' (e.g.
 * 'json-table').
 *
 *
 * @author  David R. Nadeau / University of California, San Diego
 *
 * @date    2/8/2016
 *
 * @since   0.0.1
 *
 * @version 0.0.1  Initial development.
 */
abstract class AbstractData
{
//----------------------------------------------------------------------
// Fields
//----------------------------------------------------------------------
    /**
     * @var  array $attributes
     * An associative array of named data attributes.
     */
    private $attributes;





//----------------------------------------------------------------------
// Constants
//----------------------------------------------------------------------
    /**
     * @var array WELL_KNOWN_ATTRIBUTES
     * An associative array where the keys are the names of well-known
     * data attributes.
     */
    private static $WELL_KNOWN_ATTRIBUTES = array(
        'name' => 1,
        'longName' => 1,
        'description' => 1,
        'sourceFileName' => 1,
        'sourceMIMEType' => 1,
        'sourceSyntax' => 1,
        'sourceSchemaName' => 1
    );

    private static $ERROR_attributes_argument_invalid =
        'Data attributes must be an array or object.';
    private static $ERROR_attribute_key_invalid =
        'Data attribute keys must be non-empty strings.';
    private static $ERROR_attribute_wellknown_key_value_invalid =
        'Data attribute values for well-known keys must be strings.';





//----------------------------------------------------------------------
// Constructors & Destructors
//----------------------------------------------------------------------
    /**
     * @name Constructors
     */
    // @{
    /**
     * Constructs an empty object with the given initial attribute values.
     *
     * @param   array $attributes  an associatve array of data attributes.
     *
     * @return  object             returns a new empty object with the
     * provided attributes.
     *
     * @throws \InvalidArgumentException  if $attributes is not an array,
     * object, or NULL, or if any of its attributes have invalid keys or
     * values.
     */
    public function __construct( $attributes = NULL )
    {
        $this->attributes = array( );
        if ( !is_null( $attributes ) )
            $this->setAttributes( $attributes );
    }
    // @}

    /**
     * @name Destructors
     */
    // @{
    /**
     * Destroys the previously constructed table.
     */
    public function __destruct( )
    {
        // No action required.
    }
    // @}





//----------------------------------------------------------------------
// Utilities
//----------------------------------------------------------------------
    /**
     * @name Utilities
     */
    // @{
    /**
     * Returns an array of keywords built by tokenizing the given text
     * and removing all punctuation and stand-alone numbers, then splitting
     * the text on white space.
     *
     * All text is converted to lower case. The returned array is sorted
     * alphabetically, in a natural order, and duplicate words removed.
     *
     * This method is primarily used to build keyword lists.
     *
     * @param   string  $text   the text to tokenize into keywords
     *
     * @return  array           the array of keywords.
     */
    protected function textToKeywords( &$text )
    {
        // 1. Replace all punctuation with spaces, except for - and _.
        //
        // This will leave text with space-delimited words, where each
        // word is composed of alpha, numeric, -, and _ characters,
        // in any combination (e.g. "word", "word42", "42word", "wo-rd",
        // "wo_rd", "_word", "-word", "42", "-42", "42_", etc.).
        //
        // Note that floating-point numbers will be turned into two
        // tokens when the decimal is removed. This is OK because we
        // will be removing pure numeric tokens below (e.g. "42.5" becomes
        // "42 5", and "myfile.png" becomes "myfile png").
        $t = preg_replace( '/[^\w-]+/', ' ', strtolower( $text ) );


        // 2. Tokenize by splitting on white space.
        //
        // This produces an array of words. Empty words are skipped.
        $words = preg_split( '/[\s]+/', $t, -1, PREG_SPLIT_NO_EMPTY );


        // 3. Remove purely numeric tokens.
        //
        // Run through the list and remove any word that is numeric.
        foreach ( $words as $key => $word )
        {
            if ( is_numeric( $word ) )
                unset( $words[$key] );
        }


        // 4. Sort the keywords and remove duplicates.
        sort( $words, SORT_NATURAL | SORT_FLAG_CASE );
        return array_unique( $words );
    }

    /**
     * Returns a text representation of the given value, intended
     * for future use in building keyword lists, such as for search
     * indexes.
     *
     * String values are returned as-is. Numeric, boolean, null, and
     * other scalar types are ignored and returned as an empty
     * string (since these values do not contribute to keyword lists).
     * Array and object values are converted to a string representation.
     *
     * Returned text may include punctuation and numbers, even though
     * pure non-string values are ignored. For example, a string value
     * may read "42" and will be returned, and an array of numbers will
     * be returned as text containing those numbers. It is up to the
     * caller to do further filtering of the text to remove all numbers
     * and punctuation.
     *
     * @param   mixed   $value  the value to convert to text
     *
     * @return  string          the text version of the value, if any
     */
    protected function valueToText( &$value )
    {
        if ( is_scalar( $value ) )
        {
            // Return scalar strings as-is. Ignore all other scalars,
            // including booleans, integers, doubles, and NULLs.
            if ( is_string( $value ) )
                return $value;
            return '';
        }

        if ( is_object( $value ) &&
            method_exists( $value, "__toString" ) )
        {
            // Convert objects that support string conversion to
            // strings. This gives the object's class a chance to
            // present the object well. It may still include
            // punctuation and numbers.
            return strval( $value );
        }

        if ( is_resource( $value ) )
        {
            // There is no useful way to dump a resource. Return
            // nothing.
            return '';
        }

        // Dump arrays and objects. When dumped, we
        // get repeated use of keywords like "Array" and "Object".
        // Delete these before returning the text.
        return preg_replace( '/(Array|Object)/', '',
            var_export( $value, true ) );
    }
    // @}





//----------------------------------------------------------------------
// Data attributes methods
//----------------------------------------------------------------------
    /**
     * @name Data attributes methods
     */
    // @{
    /**
     * Clears all data attributes without affecting any other data
     * content.
     *
     * Example:
     * @code
     *   $data->clearAttributes( );
     * @endcode
     */
    public function clearAttributes( )
    {
        $this->attributes = array( );
    }

    /**
     * Returns a copy of the selected data attribute, or a NULL if there is
     * no such attribute.
     *
     * Attribute keys must be strings. The data type of attribute values
     * varies, but all well-known attributes have string values.
     *
     * Example:
     * @code
     *   $value = $data->getAttribute( 'name' );
     * @endcode
     *
     * @param string $key  the key for a data attribute to query.
     *
     * @return mixed  returns the value for the data attribute, or a
     * NULL if there is no such attribute. The returned value may be of
     * any type, but it is typically a string or number.
     *
     * @throws \InvalidArgumentException  if $key is not a string or is empty.
     *
     * @see getAttributes( ) to get an associative array containing a
     * copy of all data attributes.
     */
    public function getAttribute( $key )
    {
        // Validate argument.
        if ( !is_string( $key ) || empty( $key ) )
            throw new \InvalidArgumentException(
                self::$ERROR_attribute_key_invalid );

        if ( !isset( $this->attributes[$key] ) )
            return NULL;                    // Key not found
        return $this->attributes[$key];
    }

    /**
     * Returns an array of keywords found in the data's attributes,
     * including the name, long name, description, and other attributes.
     *
     * Such a keyword list is useful when building a search index to
     * find this data object. The returns keywords array is in
     * lower case, with duplicate words removed, and the array sorted
     * in a natural sort order.
     *
     * The keyword list is formed by extracting all space or punctuation
     * delimited words found in all attribute keys and values. This
     * includes the name, long name, and description attributes, and
     * any others added by the application. Well known attribute
     * names, such as 'name', 'longName', etc., are not included, but
     * application-specific attribute names are included.
     *
     * Numbers and punctuation are ignored. Array and object attribute
     * values are converted to text and then scanned for words.
     *
     * @return array  returns an array of keywords.
     */
    public function getAttributeKeywords( )
    {
        // Add all attribute keys and values.
        $text = '';
        foreach ( $this->attributes as $key => &$value )
        {
            // Add the key. Skip well-known key names.  Intelligently
            // convert to text.
            if ( !isset( self::$WELL_KNOWN_ATTRIBUTES[$key] ) )
                $text .= ' ' . $this->valueToText( $key );

            // Add the value.  Intelligently convert to text.
            $text .= ' ' . $this->valueToText( $value );
        }

        // Clean the text of numbers and punctuation, and return
        // an array of keywords.
        return $this->textToKeywords( $text );
    }

    /**
     * Returns an associative array containing a copy of all data attributes.
     *
     * If the data has no attributes, an empty array is returned.
     *
     * Example:
     * @code
     *   $attributes = $data->getAttributes( );
     *   foreach ( $attributes as $key => $value )
     *   {
     *     print( "$key = $value\n" );
     *   }
     * @endcode
     *
     * @return array  returns an associative array of data attributes.
     */
    public function getAttributes( )
    {
        return $this->attributes;
    }

    /**
     * Returns a "best" data name by checking for, in order, the long name,
     * short name, and file name, and returning the first non-empty value
     * found, or an empty string if all of those are empty.
     *
     * Example:
     * @code
     *   $bestName = $data->getBestName( );
     * @endcode
     *
     * This method is a convenience function that is the equivalent of
     * checking each of the long name, name, and file name attributes
     * in order.
     *
     * @return  the best name, or an empty string if there is no name
     */
    public function getBestName( )
    {
        if ( !empty( $this->attributes['longName'] ) )
            return strval( $this->attributes['longName'] );
        if ( !empty( $this->attributes['name'] ) )
            return strval( $this->attributes['name'] );
        if ( !empty( $this->attributes['sourceFileName'] ) )
            return strval( $this->attributes['sourceFileName'] );
        return '';
    }

    /**
     * Returns the data description, or an empty string if there is
     * no description.
     *
     * Example:
     * @code
     *   $description = $data->getDescription( );
     * @endcode
     *
     * This method is a convenience function that is the equivalent of
     * getting the data's 'description' attribute.
     *
     * @return  the data description, or an empty string if there is no
     * description
     */
    public function getDescription( )
    {
        if ( !isset( $this->attributes['description'] ) )
            return '';
        return strval( $this->attributes['description'] );
    }

    /**
     * Returns the data long name, or an empty string if there is no long name.
     *
     * Example:
     * @code
     *   $longName = $data->getLongName( );
     * @endcode
     *
     * This method is a convenience function that is the equivalent of
     * getting the data's 'longName' attribute.
     *
     * @return  the data long name, or an empty string if there is no long name
     */
    public function getLongName( )
    {
        if ( !isset( $this->attributes['longName'] ) )
            return '';
        return strval( $this->attributes['longName'] );
    }

    /**
     * Returns the data name, or an empty string if there is no name.
     *
     * Example:
     * @code
     *   $name = $data->getName( );
     * @endcode
     *
     * This method is a convenience function that is the equivalent of
     * getting the data's 'name' attribute.
     *
     * @return  the data name, or an empty string if there is no name
     */
    public function getName( )
    {
        if ( !isset( $this->attributes['name'] ) )
            return '';
        return strval( $this->attributes['name'] );
    }

    /**
     * Returns the data source file name, or an empty string if there
     * is no source file name.
     *
     * Example:
     * @code
     *   $name = $data->getSourceFileName( );
     * @endcode
     *
     * This method is a convenience function that is the equivalent of
     * getting the data's 'sourceFileName' attribute.
     *
     * @return  the source file name, or an empty string if there is no name
     */
    public function getSourceFileName( )
    {
        if ( !isset( $this->attributes['sourceFileName'] ) )
            return '';
        return strval( $this->attributes['sourceFileName'] );
    }






    /**
     * Sets the value for the selected data attribute, overwriting any
     * prior value or adding the attribute if it was not already present.
     *
     * Attribute keys must be strings.
     *
     * Attribute values for well-known attributes must be strings.
     *
     * Example:
     * @code
     *   $data->setAttribute( 'name', 'MyData' );
     * @endcode
     *
     * @param   string  $key    the key of a data attribute.
     *
     * @param   mixed   $value  the new value for the selected data attribute.
     *
     * @throws \InvalidArgumentException  if $key is not a string or is empty,
     * or if $value is not a string when $key is one of the well-known
     * attributes.
     */
    public function setAttribute( $key, $value )
    {
        // Validate argument.
        if ( !is_string( $key ) || empty( $key ) )
            throw new \InvalidArgumentException(
                self::$ERROR_attribute_key_invalid );
        if ( isset( self::$WELL_KNOWN_ATTRIBUTES[$key] ) &&
            !is_string( $value ) )
            throw new \InvalidArgumentException(
                self::$ERROR_attribute_wellknown_key_value_invalid );

        $this->attributes[$key] = $value;
    }

    /**
     * Sets the values for the selected data attributes, overwriting any
     * prior values or adding attributes if they were not already present.
     *
     * Attribute keys must be strings.
     *
     * Attribute values for well-known attributes must be strings.
     *
     * Example:
     * @code
     *   $attributes = array(
     *     'name' => 'MyData',
     *     'description' => 'really cool data!' );
     *   $data->setAttributes( $attributes );
     * @endcode
     *
     * @param   array $attributes  an associatve array of data attributes.
     *
     * @throws \InvalidArgumentException  if $attributes is not an array,
     * object, or NULL, or if any of its attributes have invalid keys or
     * values.
     */
    public function setAttributes( $attributes )
    {
        // Validate
        if ( !is_array( $attributes ) && !is_object( $attributes ) &&
            $attributes != NULL )
            throw new \InvalidArgumentException(
                self::$ERROR_attributes_argument_invalid );
        if ( empty( $attributes ) )
            return;                     // Request to set nothing

        // Convert object argument to an array, if needed.
        $a = (array)$attributes;

        // Insure keys are all strings and all well-known key values
        // are strings.
        foreach ( $a as $key => $value )
        {
            if ( !is_string( $key ) || empty( $key ) )
                throw new \InvalidArgumentException(
                    self::$ERROR_attribute_key_invalid );
            if ( isset( self::$WELL_KNOWN_ATTRIBUTES[$key] ) &&
                !is_string( $value ) )
                throw new \InvalidArgumentException(
                    self::$ERROR_attribute_wellknown_key_value_invalid );
        }

        // Set.
        foreach ( $a as $key => $value )
        {
            $this->attributes[$key] = $value;
        }
    }

    /**
     * Sets the data description.
     *
     * Example:
     * @code
     *   $data->setDescription( "This is a description" );
     * @endcode
     *
     * This method is a convenience function that is the equivalent of
     * setting the data's 'description' attribute.
     *
     * @param   string  $description the data description.
     */
    public function setDescription( $description )
    {
        $this->attributes['description'] = strval( $description );
    }

    /**
     * Sets the data long name.
     *
     * Example:
     * @code
     *   $data->setLongName( "Long name" );
     * @endcode
     *
     * This method is a convenience function that is the equivalent of
     * setting the data's 'longName' attribute.
     *
     * @param   string  $longname  the data long name.
     */
    public function setLongName( $longname )
    {
        $this->attributes['longName'] = strval( $longname );
    }

    /**
     * Sets the data name.
     *
     * Example:
     * @code
     *   $data->setName( "Name" ;
     * @endcode
     *
     * This method is a convenience function that is the equivalent of
     * setting the data's 'name' attribute.
     *
     * @param   string  $name  the data name.
     */
    public function setName( $name )
    {
        $this->attributes['name'] = strval( $name );
    }

    /**
     * Sets the source file name.
     *
     * Example:
     * @code
     *   $data->setSourceFileName( "myfile.json" ;
     * @endcode
     *
     * This method is a convenience function that is the equivalent of
     * setting the data's 'sourceFileName' attribute.
     *
     * @param   string  $name  the source file name.
     */
    public function setSourceFileName( $name )
    {
        $this->attributes['sourceFileName'] = strval( $name );
    }
    // @}
}

/**
 * @file
 * Defines SDSC\StructuredData\Format\AbstractFormat with abstract methods to
 * parse and serialize data.
 */

namespace SDSC\StructuredData\Format;









/**
 * @class AbstractFormat
 * AbstractFormat is an abstract base class that defines a framework for
 * data type descriptions, and decode and encode methods that map between
 * a file or stream format and small data container objects, such as
 * tables, trees, and graphs.
 *
 *
 * #### Decode and encode
 * Subclasses must implement methods to decode and encode using the format
 * for a file or string argument:
 * - decode( )
 * - decodeFile( )
 * - encode( )
 * - encodeFile( )
 *
 *
 * #### Data type metadata
 * Metadata describing a file format (a.k.a. "data type") is available
 * in three ways:
 * - get...( ) methods for well-known metadata (e.g. the format name)
 * - getAttributes( ) methods for arbitrary metadata
 * - JSON text conforming to the Research Data Alliance (RDA) specifications
 *
 * The RDA's Data Type Registries Working Group defines a set of metadata
 * that should be defined by all formats (data types) and a JSON schema
 * for this data. The AbstractFormat class returns this text from
 * - getDataTypeRegistration( )
 *
 * AbstractFormat builds this text using values retreived by calling:
 * - getAttributes( )
 * for well-known attribute names (see below).  Subclasses must implement
 * this method.
 *
 * AbstractFormat also provides a few methods to get specific attributes:
 * - getName( )
 * - getLongName( )
 * - getDescription( )
 * - getSyntax( )
 * - getFileExtensions( )
 * - getMIMEType( )
 *
 * These methods call getAttributes( ) with appropriate attribute names.
 *
 *
 * #### Metadata names
 * The following attributes have scalar string values:
 * - "name" - a short name for the format
 * - "longName" - a longer name for the format
 * - "description" - a 1-2 sentence description of the format
 * - "syntax" - a short name for the base syntax
 * - "identifier" - an RDA identifier for the format
 * - "creationDate" - the date the format was created, if known
 * - "lastModificationDate" - the last time the format was modified
 * - "MIMEType" - the MIME type for the format
 *
 * The date fields should be in date-time format
 * (e.g. "2015-10-12T11:58:04.566Z").
 *
 * The following attributes have values that are an array of strings:
 * - "expectedUses" - a list of 1-2 sentences for a few uses
 * - "fileExtensions" - a list of filename extensions (no leading dot)
 *
 * The following attributes have special array values:
 * - "standards" - the standards implemented
 * - "contributors" - the format's list of contributors
 *
 * The standards array is an array of associative arrays, where each
 * associative array has these scalar string values:
 * - "issuer" - one of "DTR", "ISO", "W3C", "ITU", or "RFC"
 * - "name" - the identifier in the above format
 * - "details" - a description of how the standard was used
 * - "natureOfApplicability" - one of "extends", "constrains",
 * "specifies", or "depends"
 *
 * The contributors array is an array of entries that list the contributors
 * to the format's specification. Each entry is an associative array with
 * these keys:
 * - "identifiedUsing" - one of "text", "URL", "ORCID", or "Handle"
 * - "name" - the identifier in the above format
 * - "details" - a description of how that previous version was used
 *
 *
 * #### Research Data Alliance (RDA) equivalents
 * All of the above, except "longName", have equivalents for RDA's
 * data type registries.
 *
 * RDA's "properties", "representationsAndSemantics", and "relationships"
 * attributes are not available here because RDA doesn't have firm
 * definitions yet.
 *
 *
 *
 * @author  David R. Nadeau / University of California, San Diego
 *
 * @date    1/27/2016
 *
 * @since   0.0.1
 *
 * @version 0.0.1  Initial development.
 *
 * @version 0.0.2  Revised to manage format attributes per RDA.
 */
abstract class AbstractFormat
{
//----------------------------------------------------------------------
// Fields
//----------------------------------------------------------------------
    /**
     * @var  array $attributes
     * An associative array of named format attributes.
     *
     * The table's attributes array may contain additional format-specific
     * attributes.
     */
    protected $attributes;




//----------------------------------------------------------------------
// Constructors & Destructors
//----------------------------------------------------------------------
    /**
     * @name Constructors
     */
    // @{
    /**
     * Constructs and returns a new format object that may be used to
     * decode and encode data.
     */
    protected function __construct( )
    {
        $this->attributes = array( );
    }
    // @}

    /**
     * @name Destructors
     */
    // @{
    /**
     * Destroys a previously-constructed format object.
     */
    public function __destruct( )
    {
    }
    // @}





//----------------------------------------------------------------------
// Encode/decode attribute methods
//----------------------------------------------------------------------
    /**
     * @name Encode/decode attribute methods
     */
    // @{
    /**
     * Returns the format's relative complexity as a number between
     * 0 (low) and 10 (high).
     *
     * A high complexity format has specific syntactic elements it searches
     * for, and a high ability to reject content that doesn't have
     * those elements.  Formats like HTML and XML are high complexity.
     *
     * A low complexity format is relaxed in its parsing and may accept
     * almost any content as valid and come up with a default object to
     * return.  TEXT is the lowest complexity possible since empty
     * text or text with any characters at all is still valid text.
     * A format like CSV (comma-separated values) is very low complexity
     * since an input with just one non-whitespace character defines
     * a minimal 1-column 1-row table, making any input valid.
     *
     * A format's complexity rating is used by FormatRegistry to
     * prioritize checking high complexity formats before low complexity
     * formats when parsing an unknown input.  The high complexity formats
     * will reject the input if it doesn't match their syntactic rules,
     * leaving the low complexity formats as a fallback.
     */
    public function getComplexity( )
    {
        return 0;
    }

    /**
     * Returns true if the given object can be encoded into this format.
     *
     * @param   object  $object the object to test for encodability
     *
     * @return  boolean returns true if the object can be encoded, and
     * false otherwise.
     */
    public function canEncode( $object = NULL )
    {
        if ( $object == NULL )
            return false;

        if ( $object instanceof \SDSC\StructuredData\Table )
            return $this->canEncodeTables( );

        if ( $object instanceof \SDSC\StructuredData\Tree )
            return $this->canEncodeTrees( );

        if ( $object instanceof \SDSC\StructuredData\Graph )
            return $this->canEncodeGraphs( );

        return false;
    }

    /**
     * Returns true if the format can decode one or more graphs 
     * described by an SDSC\StructuredData\Graph.
     *
     * @return  boolean  returns true if the format supports decoding
     * one or more graphs.
     */
    public function canDecodeGraphs( )
    {
        return false;
    }

    /**
     * Returns true if the format can encode one or more graphs 
     * described by an SDSC\StructuredData\Graph.
     *
     * @return  boolean  returns true if the format supports encoding
     * one or more graphs.
     */
    public function canEncodeGraphs( )
    {
        return false;
    }

    /**
     * Returns true if the format can decode one or more tables 
     * described by an SDSC\StructuredData\Table.
     *
     * @return  boolean  returns true if the format supports decoding
     * one or more tables.
     */
    public function canDecodeTables( )
    {
        return false;
    }

    /**
     * Returns true if the format can encode one or more tables 
     * described by an SDSC\StructuredData\Table.
     *
     * @return  boolean  returns true if the format supports encoding
     * one or more tables.
     */
    public function canEncodeTables( )
    {
        return false;
    }

    /**
     * Returns true if the format can decode one or more trees 
     * described by an SDSC\StructuredData\Tree.
     *
     * @return  boolean  returns true if the format supports decoding
     * one or more trees.
     */
    public function canDecodeTrees( )
    {
        return false;
    }

    /**
     * Returns true if the format can encode one or more trees 
     * described by an SDSC\StructuredData\Tree.
     *
     * @return  boolean  returns true if the format supports encoding
     * one or more trees.
     */
    public function canEncodeTrees( )
    {
        return false;
    }
    // @}





//----------------------------------------------------------------------
// Attribute methods
//----------------------------------------------------------------------
    /**
     * @name Attribute methods
     */
    // @{
    /**
     * Returns a copy of the named attribute for the format.
     *
     * Example:
     * @code
     *   $name = $format->getAttribute( 'name' );
     * @endcode
     *
     * @param   string  $key  the name of an attribute for the format.
     *
     * @return  varies  returns a string, array, or other type of value
     * associated with the named attribute.
     */
    public function getAttribute( $key )
    {
        if ( empty( $key ) )
            return NULL;                            // Request with no name
        if ( !isset( $this->attributes[$key] ) )
            return NULL;                            // No such attribute
        return $this->attributes[(string)$key];
    }

    /**
     * Returns an associative array containing all attributes for
     * the format.
     *
     * Example:
     * @code
     *   $attributes = $format->getAttributes( );
     * @endcode
     *
     * @return  array  returns an associative array containing all attributes
     * for the format.
     */
    public function getAttributes( )
    {
        return $this->attributes;
    }

    /**
     * Returns a "best" format name by checking for, in order, the long name,
     * short name, and syntax name, and returning the first non-empty value
     * found, or an empty string if all of those are empty.
     *
     * Example:
     * @code
     *   $bestName = $data->getBestName( );
     * @endcode
     *
     * This method is a convenience function that is the equivalent of
     * checking each of the long name, name, and syntax attributes
     * in order.
     *
     * @return  the best name, or an empty string if there is no name
     */
    public function getBestName( )
    {
        if ( !empty( $this->attributes['longName'] ) )
            return strval( $this->attributes['longName'] );
        if ( !empty( $this->attributes['name'] ) )
            return strval( $this->attributes['name'] );
        if ( !empty( $this->attributes['syntax'] ) )
            return strval( $this->attributes['syntax'] );
        return '';
    }

    /**
     * Returns the RDA data type registration in JSON syntax based
     * upon the RDA JSON schema.
     *
     * Example:
     * @code
     *   $reg = $format->getDataTypeRegistration( );
     * @endcode
     *
     * @return  string  returns a JSON-formated string describing an
     * object with properties and nested objects and arrays that
     * describes the data type using RDA's JSON schema
     */
    public function getDataTypeRegistration( )
    {
        // Build up an object with the content to output.
        $out = array( );

        // Name (required)
        //  "name": "format-name"
        $out['name'] = 'unnamed';
        if ( isset( $this->attributes['name'] ) &&
            is_scalar( $this->attributes['name'] ) )
            $out['name'] = strval( $this->attributes['name'] );

        // Description (required)
        //  "description": "format-description"
        $out['description'] = '';
        if ( isset( $this->attributes['description'] ) &&
            is_scalar( $this->attributes['description'] ) )
            $out['description'] = strval( $this->attributes['description'] );

        // Expected uses (optional)
        //  "expectedUses": [ "use1", "use2", "use3", ... ]
        if ( isset( $this->attributes['expectedUses'] ) &&
            is_array( $this->attributes['expectedUses'] ) )
        {
            // "expectedUses" must be an array of strings.
            $eu = array( );
            foreach ( $this->attributes['expectedUses'] as &$entry )
            {
                if ( is_scalar( $entry ) )
                    $eu[] = strval( $entry );
            }

            if ( !empty( $eu ) )
                $out['expectedUses'] = $eu;
        }

        // Standards (optional)
        //  "standards": [
        //    {
        //      "name": "standard-name",
        //      "details": "standard-details",
        //      "issuesr": "standard-issuer",
        //      "natureOfApplicability": "standard-applicability"
        //    },
        //    { ... }
        //  ]
        $standards = NULL;
        if ( isset( $this->attributes['standards'] ) &&
            is_array( $this->attributes['standards'] ) )
        {
            // "standards" must be an array of associative arrays.
            // Each associative array provides attributes of a different
            // relevant standard.
            $standards = array( );

            foreach ( $this->attributes['standards'] as &$entry )
            {
                if ( !is_array( $entry ) )
                    continue;       // Bogus entry, skip it

                // Each standards entry is expected to have:
                //   "name" (required)
                //   "issuer" (required + fixed vocabulary)
                //   "details" (optional)
                //   "natureOfApplicability" (optional + fixed vocabulary)
                //
                // All of these must be strings. If they are not, they
                // are converted to strings.

                if ( !isset( $entry['name'] ) ||
                    !is_scalar( $entry['name'] ) )
                    continue;       // No name, skip it

                $e = new \stdClass( );
                $e->name = strval( $entry['name'] );

                if ( isset( $entry['issuer'] ) &&
                    is_scalar( $entry['issuer'] ) )
                    $e->issuer = strval( $entry['issuer'] );

                if ( isset( $entry['details'] ) &&
                    is_scalar( $entry['details'] ) )
                    $e->details = strval( $entry['details'] );

                if ( isset( $entry['natureOfApplicability'] ) &&
                    is_scalar( $entry['natureOfApplicability'] ) )
                    $e->natureOfApplicability = strval( $entry['natureOfApplicability'] );

                $standards[] = $e;
            }

            if ( !empty( $standards ) )
                $out['standards'] = $standards;
        }

        // Provenance (optional)
        $prov = new \stdClass;
        if ( isset( $this->attributes['creationDate'] ) &&
            is_scalar( $this->attributes['creationDate'] ) )
            $prov->creationDate = strval( $this->attributes['creationDate'] );

        if ( isset( $this->attributes['lastModificationDate'] ) &&
            is_scalar( $this->attributes['lastModificationDate'] ) )
            $prov->lastModificationDate = strval( $this->attributes['lastModificationDate'] );

        if ( isset( $this->attributes['contributors'] ) &&
            is_array( $this->attributes['contributors'] ) )
        {
            // "contributors" must be an array of associative arrays.
            // Each associative array provides attributes of a different
            // relevant contributor.
            $contributors = array( );

            foreach ( $this->attributes['contributors'] as &$entry )
            {
                if ( !is_array( $entry ) )
                    continue;       // Bogus entry, skip it

                // Each contributors entry is expected to have:
                //   "name" (required)
                //   "identifiedUsing" (required + fixed vocabulary)
                //   "details" (optional)
                // All of these must be strings.

                if ( !isset( $entry['name'] ) ||
                    !is_scalar( $entry['name'] ) )
                    continue;       // No name, skip it

                $e = new \stdClass;
                $e->name = strval( $entry['name'] );

                if ( isset( $entry['identifiedUsing'] ) &&
                    is_scalar( $entry['identifiedUsing'] ) )
                    $e->identifiedUsing = strval( $entry['identifiedUsing'] );

                if ( isset( $entry['details'] ) &&
                    is_scalar( $entry['details'] ) )
                    $e->details = strval( $entry['details'] );

                $contributors[] = $e;
            }

            if ( !empty( $contributors ) )
                $prov->contributors = $contributors;
        }
        if ( !empty( $prov ) )
            $out['provenance'] = $prov;


        // Generate JSON
        return json_encode( $out, JSON_PRETTY_PRINT );

    }

    /**
     * Returns a string containing a brief description of the format,
     * suitable for display has help information about the format.
     *
     * The string may be several sentences, with punctuation, but
     * without carriage returns or other formatting. The description
     * characterizes the type of data that may be encoded in the format,
     * without discussing specific syntax.
     *
     * Example:
     * @code
     *   $string = $format->getDescription( );
     * @endcode
     *
     * @return  string   returns a string containing a block of text
     * that describes the format in lay terms suitable for use within
     * a user interface, or an empty string if no description is available.
     */
    public function getDescription( )
    {
        if ( !isset( $this->attributes['description'] ) )
            return '';
        return $this->attributes['description'];
    }

    /**
     * Returns an array containing strings for file name extensions
     * commonly associated with the format.
     *
     * Array entries include extensions without a leading ".". All
     * extensions are case insensitive.
     *
     * The returned array is empty if there are no common extensions
     * for the format.
     *
     * Example:
     * @code
     *   $array = $format->getFileExtensions( );
     * @endcode
     *
     * @return  array  returns an array of strings with one entry for
     * each well-known file name extension associated with the format;
     * or an empty array if there are no common file name extensions.
     */
    public function getFileExtensions( )
    {
        if ( !isset( $this->attributes['fileExtensions'] ) )
            return array( );

        // Only return entries that are strings. All of them should be.
        $rfe = array( );
        foreach ( $this->attributes['fileExtensions'] as &$entry )
        {
            if ( is_string( $entry ) )
                $rfe[] = $entry;
        }
        return $rfe;
    }

    /**
     * Returns a string containing a long name for the format,
     * such as a multi-word name that spells out an acryonym.
     *
     * The string may be several words, separated by spaces or
     * punctuation, but without carriage returns or other formatting.
     *
     * Example:
     * @code
     *   $string = $format->getLongName( );
     * @endcode
     *
     * @return  string  returns a string containing a longer multi-word
     * name for the format that often spells out acronyms.
     */
    public function getLongName( )
    {
        if ( !isset( $this->attributes['longName'] ) )
            return NULL;
        return $this->attributes['longName'];
    }

    /**
     * Returns a string containing the MIME type for the format, if any.
     *
     * Example:
     * @code
     *   $string = $format->getMIMEType( );
     * @endcode
     *
     * @return  string   returns a string containing the MIME type,
     * or an empty string if there is none.
     */
    public function getMIMEType( )
    {
        if ( !isset( $this->attributes['MIMEType'] ) )
            return '';
        return $this->attributes['MIMEType'];
    }

    /**
     * Returns a string containing a short name for the format,
     * such as a single word or acronym.
     *
     * The string may be a word or two, separated by spaces or
     * punctuation, but without carriage returns or other formatting.
     *
     * Example:
     * @code
     *   $string = $format->getName( );
     * @endcode
     *
     * @return  string  returns a string containing a short name
     * for the format, such as brief word or two, an abbreviation,
     * or an acryonym.
     */
    public function getName( )
    {
        if ( !isset( $this->attributes['name'] ) )
            return NULL;
        return $this->attributes['name'];
    }

    /**
     * Returns a string containing the base syntax used by the format.
     *
     * The string may be a word or two, separated by spaces or
     * punctuation, but without carriage returns or other formatting.
     *
     * The syntax name is sometimes the same as the name of the format,
     * but it need not be. The "CSV" format for comma-separated values,
     * for instance, has a syntax named "CSV" too. But the "JSON Table"
     * format's syntax is simply "JSON". And an assortment of XML-based
     * formats may have different names, but a syntax of "XML".
     *
     * Example:
     * @code
     *   $string = $format->getSyntax( );
     * @endcode
     *
     * @return  string  returns a string containing the name of the syntax
     * used by the format, such as brief word or two, an abbreviation,
     * or an acryonym.
     */
    public function getSyntax( )
    {
        if ( !isset( $this->attributes['syntax'] ) )
            return NULL;
        return $this->attributes['syntax'];
    }

    /**
     * Returns true if the given file name extension is supported by
     * this format.
     *
     * Example:
     * @code
     *   if ( $format->isFileExtension( 'csv' ) )
     *   {
     *     ...
     *   }
     * @endcode
     *
     * @param   $extension  the file name extension, without a dot
     *
     * @return  boolean     true if the given extension is one supported
     * by this format.
     */
    public function isFileExtension( $extension )
    {
        if ( empty( $extension ) )
            return false;
        if ( !isset( $this->attributes['fileExtensions'] ) )
            return false;

        foreach ( $this->attributes['fileExtensions'] as &$entry )
        {
            if ( $entry === $extension )
                return true;
        }
        return false;
    }
    // @}





//----------------------------------------------------------------------
// Encode/decode methods
//----------------------------------------------------------------------
    /**
     * @name Encode/decode methods
     */
    // @{
    /**
     * Parses a text string containing data in the format and returns
     * an array containing one or more data objects.
     *
     * If the text string is empty or does not containing any content
     * recognized by this format, the method returns an empty array.
     * Otherwise, the returned array contains one or more objects
     * built from the parsed text.
     *
     * If parsing encounters an unrecoverable problem, the method
     * throws an exception with a brief message that describes the
     * problem. Typical problems are syntax errors in the format
     * or semantic problems, such as empty column names in a table.
     *
     * Example:
     * @code
     *   $objects = $format->decode( $text );
     * @endcode
     *
     * @param  string  $text   a text string containing data to decode
     * into an array of returned data objects.
     *
     * @return array           returns an array containing objects
     * parsed from the text, or an empty array if no parsable data
     * was found.
     *
     * @throws FormatException  if the text could not be parsed
     * properly due to a variety of format-specific syntax and content
     * errors (see SyntaxException and InvalidContentException).
     */
    abstract public function decode( &$text );

    /**
     * Parses a file containing data in the format and returns an
     * array containing one or more data objects.
     *
     * If the file name is empty, or the file is empty, or the
     * file does not containing any content recognized by this
     * format, the method returns an empty array.
     * Otherwise, the returned array contains one or more objects
     * built from the parsed file contents.
     *
     * If parsing encounters an unrecoverable problem, the method
     * throws an exception with a brief message that describes the
     * problem. Typical problems are syntax errors in the format
     * or semantic problems, such as empty column names in a table.
     *
     * Example:
     * @code
     *   $objects = $format->decodeFile( $filename );
     * @endcode
     *
     * @param  string  $filename  a text string containing the name
     * of a file containing data to decode into an array of returned
     * data objects.
     *
     * @return array           returns an array containing objects
     * parsed from the text, or an empty array if no parsable data
     * was found.
     *
     * @throws FormatException  if the text could not be parsed
     * properly due to a variety of format-specific syntax and content
     * errors (see SyntaxException and InvalidContentException).
     *
     * @throws FileNotFoundException  if the file could not be opened
     * or read, such as when the file or some directory on the file path
     * does not exist or is not readable.
     *
     * @throws \InvalidArgumentException  if the file could not be read,
     * such as due to permissions problems or system level errors.
     */
    public function decodeFile( $filename )
    {
        // Check if the file is readable.
        if ( is_readable( $filename ) === FALSE )
            throw new FileNotFoundException(
                'File or directory not found or not readable',
                0, 1, $filename );


        // Read the file's contents, catching file system errors.
        // On failure, file_get_contents issues an E_WARNING, which
        // we catch in the error handler.
        //
        // Because we've already determined that the file exists and
        // is readable, the kind of file system errors we'd get here
        // are obscure, such as if existence or permissions changed
        // suddenly, a file system became unmounted, a network file
        // system had network problems, etc.
        //
        // Because file system errors cannot be tested for in unit
        // testing, we mark this code to ignore it in code coverage
        // reports.
        // @codeCoverageIgnoreStart
        set_error_handler(
            function( $severity, $message, $file, $line )
            {
                throw new \InvalidArgumentException( $message, $severity );
            }
        );
        // @codeCoverageIgnoreEnd
        try
        {
            $text = file_get_contents( $filename );
        }
        // @codeCoverageIgnoreStart
        catch ( \Exception $e )
        {
            restore_error_handler( );
            throw $e;
        }
        restore_error_handler( );
        // @codeCoverageIgnoreEnd


        // Return an empty array if there is no file content.
        if ( $text === false || empty( $text ) )
            return array( );


        // Decode. If there are problems, this will throw a number of
        // exceptions.
        $results = $this->decode( $text );


        // Add file source attribute to every returned object.
        if ( !empty( $results ) )
        {
            $addAttribute = array( 'sourceFileName' => $filename );
            foreach ( $results as &$obj )
            {
                if ( $obj instanceof \SDSC\StructuredData\AbstractData )
                {
                    $obj->setAttributes( $addAttribute );
                }
            }
        }

        return $results;
    }





    /**
     * Encodes one or more data objects into a returned text
     * string in the format.
     *
     * The method's parameter may be a single object or an array
     * of objects to encode. Most formats expect a single object.
     * If multiple objects are passed to a format that can only
     * encode one object, the method throws an exception.
     *
     * Example:
     * @code
     *   $text = $format->encode( $objects );
     * @endcode
     *
     * @param array    $objects  an array of data objects
     * to encode into the returned text string.
     *
     * @param mixed    $options  a set of encoding options used
     * by some formats to select among encoding variants.
     *
     * @throws \InvalidArgumentException   if multiple objects are passed
     * to the format, but the format only supports encoding a
     * single object.
     *
     * @throws \InvalidArgumentException   if the array of objects is NULL,
     * empty, or it contains NULL objects to encode.
     *
     * @throws \InvalidArgumentException   if an object in the array of
     * objects to encode is not compatible with the format.
     */
    abstract public function encode( &$objects, $options = 0 );

    /**
     * Encodes one or more data objects and writes them to
     * a file in the format.
     *
     * The method's parameter may be a single object or an array
     * of objects to encode. Most formats expect a single object.
     * If multiple objects are passed to a format that can only
     * encode one object, the method throws an exception.
     *
     * Example:
     * @code
     *   $format->encodeFile( $filename, $objects );
     * @endcode
     *
     * @param  string  $filename  a text string containing the name
     * of a file to create or overwrite with the encoded data
     * objects.
     *
     * @param array    $objects  an array of data objects
     * to encode into the returned text string.
     *
     * @param mixed    $options  a set of encoding options used
     * by some formats to select among encoding variants.
     *
     * @throws \InvalidArgumentException   if multiple objects are passed
     * to the format, but the format only supports encoding a
     * single object.
     *
     * @throws \InvalidArgumentException   if the array of objects is NULL,
     * empty, or it contains NULL objects to encode.
     *
     * @throws \InvalidArgumentException   if an object in the array of
     * objects to encode is not compatible with the format.
     *
     * @throws FileNotFoundException  if the file could not be opened
     * or written, such as when the file or some directory on the file path
     * does not exist or is not writable.
     *
     * @throws \InvalidArgumentException  if the file could not be written,
     * such as due to permissions problems or system level errors.
     *
     * @throws FormatException  if the array of objects could not be encoded
     * properly for this format.
     *
     */
    public function encodeFile( $filename, &$objects, $options = 0 )
    {
        // If the file already exists, make sure it is writable.
        if ( file_exists( $filename ) === TRUE &&
            is_writable( $filename ) === FALSE )
            throw new FileNotFoundException(
                'File or directory not found or not writable',
                0, 1, $filename );


        // Encode. An encoded object that is an empty string is stil
        // writable to an outut file... it just creates an empty file.
        // This is valid, though perhaps not what the caller intended.
        $text = $this->encode( $objects, $options );


        // Read the file's contents, catching file system errors.
        // On failure, file_get_contents issues an E_WARNING, which
        // we catch in the error handler.
        //
        // Because we've already determined that the file exists and
        // is writable, the kind of file system errors we'd get here
        // are obscure, such as if existence or permissions changed
        // suddenly, a file system became unmounted, a network file
        // system had network problems, etc.
        //
        // Because file system errors cannot be tested for in unit
        // testing, we mark this code to ignore it in code coverage
        // reports.
        // @codeCoverageIgnoreStart
        set_error_handler(
            function( $severity, $message, $file, $line )
            {
                throw new \InvalidArgumentException( $message, $severity );
            }
        );
        // @codeCoverageIgnoreEnd
        try
        {
            $status = file_put_contents( $filename, $text );
        }
        // @codeCoverageIgnoreStart
        catch ( \Exception $e )
        {
            restore_error_handler( );
            throw $e;
        }
        restore_error_handler( );
        // @codeCoverageIgnoreEnd
    }
    // @}
}


/**
 * @file
 * Defines SDSC\StructuredData\Format\CSVTableFormat to parse and
 * serialize data in the Comma-Separated Value (CSV) text format.
 */

namespace SDSC\StructuredData\Format;


use SDSC\StructuredData\Table;





/**
 * @class CSVTableFormat
 * CSVTableFormat provides decode and encode functions that map
 * between Comma-Separated Values (CSV) text and a Table.
 *
 * CSV is a general-purpose text format used for the exchange of tabular
 * data, such as that used by spreadsheets (e.g. Microsoft Excel,
 * Apple Numbers) and some visualization applications. CSV files store
 * a single table with an arbitrary number of rows and columns. All
 * columns have a name. Row values may be of any data type, though they
 * are typically numeric.
 *
 *
 * #### Table syntax
 * The CSV format is documented by RFC 4180 from the IETF (Internet
 * Engineering Task Force). The RFC was never ratified as a standard and
 * it is not well-followed.
 *
 * A CSV file contains a single table made up of a list of records written
 * as lines in a text file. Each line is terminated by some mix of
 * carriage-return and line-feed:
 *
 * - RFC 4180 directs that each line end with CR-LF, in that order.
 *
 * - MS Excel 365, Apple Numbers, LibreOffice, and OpenOffice all end each
 *   line with LF.
 *
 * - MS Excel 2011 on the Mac, and MS Excel 365 saving into the "CSV for Mac"
 *   format, end all lines except the last one with a CR. The last line is
 *   ended with an LF.
 *
 * Excel saving data for the Mac is the outlier here. It is still saving
 * Mac files per the old Classic MacOS conventions that ended with the last
 * release of Classic MacOS in 2001. Modern macOS is based on BSD UNIX and
 * follows the UNIX convention of ending lines with LF.
 *
 * Values in each record are separated by commas. Numeric and other
 * single-word values may be given directly, while multi-word values are
 * enclosed in double quotes. Quoted values may include carriage returns
 * and linefeeds, though this is rare. Quoted values may include a double
 * quote by preceding it with an additional double quote.
 *
 * The first record in a CSV file may include the names for table columns,
 * however:
 *
 * - There is no syntax within a CSV file to indicate if the first line is
 *   a header or not.
 *
 * - RFC 4180 recommends using a MIME type argument to indicate that a header
 *   is present, however this requires first detecting the header in order to
 *   set the MIME type. Yet there is no way to do so.
 *
 * - RFC 4180 and common use all use the .csv file name extension, which does
 *   not have a MIME type or any indication that a header is present.
 *
 * So, while a CSV header row is optional, there is no way to detect when it
 * is or is not there. We are forced to follow overwhelming convention that
 * the first row is always a header.
 *
 * All further records provide table data. Every record must have the
 * same number of values.
 *
 *
 * #### Table decode limitations
 * Description: CSV files do not support descriptions. The returned table's
 * description is left empty.
 *
 * Name: CSV files do not support table names. The returned table's short
 * and long names are left empty.
 *
 * Column names: This class assumes the first row of the CSV file contains
 * the names of columns.  The returned table uses these CSV names as column
 * short names, but leaves column long names empty.
 *
 * Column data types: The CSV syntax does not provide data types for column
 * values, these data types are automatically inferred by the
 * SDSC\StructuredData\Table class. That class scans through each column and
 * looks for a consistent interpretation of the values as integers,
 * floating-point numbers, booleans, etc., then sets the data type
 * accordingly.
 *
 *
 * #### Table encode limitations
 * Since CSV does not support descriptive information for the table,
 * the table's short name, long name, and description are not included
 * in the encoded text.
 *
 * Since CSV only supports a single name for each column, the table's
 * column short names are output to the encoded text, but the column
 * long names, descriptions, and data types are not included.
 *
 * Column value data types are used to guide CSV encoding. Values
 * that are integers, floating-point numbers, booleans, or nulls are
 * output as single un-quoted tokens. All other value types are output
 * as double-quoted strings.
 *
 *
 * @see     SDSC\StructuredData\Table   the StructuredData Table class
 *
 * @author  David R. Nadeau / University of California, San Diego
 *
 * @date    9/24/2018
 *
 * @since   0.0.1
 *
 * @version 0.0.1  Initial development.
 *
 * @version 0.0.2  Revised to provide format attributes per RDA, and to
 * create tables using the updated Table API that uses an array of attributes.
 *
 * @version 0.0.3. Revised to support parsing Mac Excel CSV files, which use
 * CR line endings for middle file lines, and LF for the last line of the file.
 */
final class CSVTableFormat
    extends AbstractFormat
{
//----------------------------------------------------------------------
// Constructors & Destructors
//----------------------------------------------------------------------
    /**
     * @name Constructors
     */
    // @{
    /**
     * Constructs and returns a new format object that may be used to
     * decode and encode tables in CSV.
     */
    public function __construct( )
    {
        parent::__construct( );

        $this->attributes['syntax']         = 'CSV';
        $this->attributes['name']           = 'CSV';
        $this->attributes['longName']       = 'Comma-Separated Values (CSV)';
        $this->attributes['MIMEType']       = 'text/csv';
        $this->attributes['fileExtensions'] = array( 'csv' );
        $this->attributes['description']    =
            'The CSV (Comma-Separated Values) format encodes tabular data ' .
            'with an unlimited number of rows and columns. Each column has ' .
            'a short name. All rows have a value for every column. Row ' .
            'values are typically integers or floating-point numbers, but ' .
            'they also may be strings and booleans.';
        $this->attributes['expectedUses'] = array(
            'Tabular data with named columns and rows of values' );
        $this->attributes['standards'] = array(
            array(
                'issuer' => 'RFC',
                'name' => 'IETF RFC 4180',
                'natureOfApplicability' => 'specifies',
                'details' => 'Common Format and MIME Type for Comma-Separated Values (CSV) Files'
            )
        );
        $this->attributes['creationDate']         = '2005-10-01 00:00:00';
        $this->attributes['lastModificationDate'] = '2005-10-01 00:00:00';

        $this->attributes['contributors'] = array(
            array(
                'name'            => 'Y. Shafranovich',
                'details'         => 'SolidMatrix Technologies, Inc',
                'identifiedUsing' => 'Text'
            )
        );

        // Unknown:
        //  identifier
    }
    // @}

    /**
     * @name Destructors
     */
    // @{
    /**
     * Destroys a previously-constructed format object.
     */
    public function __destruct( )
    {
        parent::__destruct( );
    }
    // @}





//----------------------------------------------------------------------
// Encode/decode attribute methods
//----------------------------------------------------------------------
    /**
     * @name Encode/decode attribute methods
     */
    // @{
    /**
     * @copydoc AbstractFormat::getComplexity
     */
    public function getComplexity( )
    {
        return 2;
    }

    /**
     * @copydoc AbstractFormat::canDecodeTables
     */
    public function canDecodeTables( )
    {
        return true;
    }

    /**
     * @copydoc AbstractFormat::canEncodeTables
     */
    public function canEncodeTables( )
    {
        return true;
    }
    // @}





//----------------------------------------------------------------------
// Encode/decode methods
//----------------------------------------------------------------------
    /**
     * @name Encode/decode methods
     */
    // @{
    /**
     * @copydoc AbstractFormat::decode
     *
     * #### Decode limitations
     * The CSV format always returns an array containing a single
     * SDSC\StructuredData\Table object.
     */
    public function decode( &$text )
    {
        // PHP's str_getcsv( ) does not parse IETF RFC 4180
        // compliant CSV files properly. Further, it parses only
        // a single line of text, so parsing an entire table
        // requires exploding the text into rows first. But this
        // requires handling embedded carriage returns and line feeds
        // in the text, which can't be done with a simple PHP
        // explode( ). So we are forced to use a custom parser.
        //
        // IETF RFC 4180 and common use differ on the treatment of line
        // endings:
        //
        // - RFC 4180 directs that each line end with CR-LF, in that order.
        //
        // - MS Excel 365, Apple Numbers, LibreOffice, and OpenOffice all 
        //   end each line with LF.
        //
        // - MS Excel 2011 on the Mac, and MS Excel 365 saving into the
        //   "CSV for Mac" format, end all lines except the last one with
        //   a CR. The last line is ended with an LF.

        //
        // Preprocess
        // -----------------------------------------------------
        // Sweep through the string and execute a function on every
        // double-quoted string. In each one, replace special characters with
        // a special marker. There are four special characters:
        //
        // - Comma = \eC.
        // - Carriage-return = \eR.
        // - Linefeed = \eN.
        // - Double-quote = \eQ.
        //
        // \e is the ESCAPE character. Here we *ASSUME* that none of the
        // above escape sequences will occur within quoted text. This seems
        // very unlikely, but if they do this approach will garble the data.
        //
        if ( empty( $text ) )
            return array( );        // No table

        $markedText = preg_replace_callback(
            '/([^"]*)("((""|[^"])*)"|$)/s',
            // ----- look for all characters up to a "
            //        - look for a " to start the string
            //           --------- look for "" or all characters up to a "
            //                       - or look for the end of line
            //
            //......................... = $match[0] = whole string
            //(.....)                   = $match[1] = text up to string
            //       (................) = $match[2] = string with quotes & EOL
            //         (..........)     = $match[3] = string without quotes
            //          (.......)       = $match[4] = string without quotes
            function( $match )
            {
                // If the match doesn't find any quoted strings,
                // then return the original text
                if ( count( $match ) < 4 )
                    return $match[0];

                // Carriage returns and line feeds within quoted text will
                // confuse a later explode, so replace them with a special
                // marker sequence.  The marker uses a CR, which we'll
                // later insure cannot occur outside of quoted text.

                // Use the string without quotes and replace CR & LF
                // with a CR marker.
                $str = str_replace( "\r", "\eR", $match[3] );
                $str = str_replace( "\n", "\eN", $str );

                // Replace embedded double quotes with a CR marker.
                $str = str_replace( '""', "\eQ", $str );

                // Replace embedded commas with a CR marker.
                $str = str_replace( ',',  "\eC", $str );

                // Replace CRLF in the text before the string with
                // just LF. Replace CR alone with just LF.
                //$before = preg_replace( '/\r\n?/', "\n", $match[1]);

                // Append the processed quoted string, now without quotes
                // and without embedded LF, quotes, or commas.
                return $match[1] . $str;
            }, $text );

        // Unify the line-ending style by replacing all CR-LF, LF-CR, CR,
        // and LF endings with LF alone.
        $markedText = preg_replace( '/\r\n?/', "\n", $markedText);
        $markedText = preg_replace( '/\n\r?/', "\n", $markedText);

        // Remove the last LF, if any, so that exploding on LF
        // doesn't leave us an extra empty line at the end.
        $markedText = preg_replace( '/\n$/', '', $markedText );


        //
        // Explode
        // -----------------------------------------------------
        // Explode the string into lines on LF. We've already
        // insured that LF doesn't exist in any quoted text.
        $lines = explode( "\n", $markedText );
        unset( $markedText );


        //
        // Parse
        // -----------------------------------------------------
        // Explode each line on a comma, then unmark the marked
        // text inside double-quote values.
        $rows = array_map(
            function( $line )
            {
                $fields = explode( ',', $line );
                return array_map(
                    function( $field )
                    {
                        // Un-escape the previously marked characters.
                        $field = str_replace( "\eC", ',', $field );
                        $field = str_replace( "\eQ", '"', $field );
                        $field = str_replace( "\eN", '\n', $field );
                        $field = str_replace( "\eR", '\r', $field );
                        return $field;
                    }, $fields );
            }, $lines );
        unset( $lines );

        // If there are no rows, the file was empty and there is
        // no table to return.
        //
        // This 'if' checks will stay in the code, but there appears
        // to be no way to trigger it. An empty string '' is caught
        // earlier. A white-space string '   ' is really one row of
        // text in one column.  An empty quote string '""' is also
        // really one row of text. So, there is no obvious way to
        // hit this condition, but let's be paranoid.
        // @codeCoverageIgnoreStart
        if ( count( $rows ) == 0 )
            return array( );
        // @codeCoverageIgnoreEnd

        // The first row should be the column names. We have no way
        // of knowing if it is or not, so we just have to hope.
        $header   = array_shift( $rows );
        $nColumns = count( $header );

        // An empty file parsed as CSV produces a single column,
        // no rows, and a column with an empty name. Catch this
        // and return a NULL.
        if ( count( $rows ) == 0 && $nColumns == 1 && empty( $header[0] ) )
            return array( );

        // Every row must have the same number of values, and that
        // number must match the header.
        foreach ( $rows as &$row )
        {
            if ( count( $row ) != $nColumns )
                throw new SyntaxException(
                    'CSV table rows must all have the same number of values as the first row.' );
        }


        //
        // Build the table
        // -----------------------------------------------------
        $attributes = array(
            // 'name' unknown
            // 'longName' unknown
            // 'description' unknown
            // 'sourceFileName' unknown
            'sourceMIMEType'   => $this->getMIMEType( ),
            'sourceSyntax'     => $this->getSyntax( ),
            'sourceSchemaName' => $this->getName( )
        );
        $table = new Table( $attributes );


        //
        // Add columns
        // -----------------------------------------------------
        // Header provides column names.
        // No column descriptions or data types.
        foreach ( $header as &$field )
            $table->appendColumn( array( 'name' => $field ) );


        // Convert values rows
        // -----------------------------------------------------
        // So far, every value in every row is a string. But
        // we'd like to change to the "best" data type for
        // the value. If it is an integer, make it an integer.
        // If it is a float, make it a double. If it is a
        // boolean, make it a boolean. Only fall back to string
        // types if nothing better will do.
        foreach ( $rows as &$row )
        {
            foreach ( $row as $key => &$value )
            {
                // Ignore any value except a string. But really,
                // they should all be strings so we're just being
                // paranoid.
                // @codeCoverageIgnoreStart
                if ( !is_string( $value ) )
                    continue;
                // @codeCoverageIgnoreEnd

                $lower = strtolower( $value );
                if ( is_numeric( $value ) )
                {
                    // Convert to float or int.
                    $fValue = floatval( $value );
                    $iValue = intval( $value );

                    // If int and float same, then must be an int
                    if ( $fValue == $iValue )
                        $row[$key] = $iValue;
                    else
                        $row[$key] = $fValue;
                }
                else if ( $lower === 'true' )
                    $row[$key] = true;
                else if ( $lower === 'false' )
                    $row[$key] = false;

                // Otherwise leave it as-is.
            }
        }


        // Add rows
        // -----------------------------------------------------
        // Parsed content provides rows.
        if ( count( $rows ) != 0 )
            $table->appendRows( $rows );
        return array( $table );
    }




    /**
     * @copydoc AbstractFormat::encode
     *
     * #### Encode limitations
     * The CSV format only supports encoding a single
     * SDSC\StructuredData\Table in the format. An exception is thrown
     * if the $objects argument is not an array, is empty, contains
     * more than one object, or it is not a Table.
     */
    public function encode( &$objects, $options = '' )
    {
        //
        // Validate arguments
        // -----------------------------------------------------
        if ( $objects == NULL )
            return NULL;            // No table to encode

        if ( !is_array( $objects ) )
            throw new \InvalidArgumentException(
                'CSV encode requires an array of objects.' );

        if ( count( $objects ) > 1 )
            throw new \InvalidArgumentException(
                'CSV encode only supports encoding a single object.' );

        $table = &$objects[0];
        if ( !is_a( $table, 'SDSC\StructuredData\Table', false ) )
            throw new \InvalidArgumentException(
                'CSV encode object must be an SDSC\StructuredData\Table.' );

        $nColumns = $table->getNumberOfColumns( );
        if ( $nColumns <= 0 )
            return NULL;            // No data to encode
        $nRows = $table->getNumberOfRows( );
        $text  = '';


        //
        // Encode header
        // -----------------------------------------------------
        //   Ignore the table name and other attributes since
        //   CSV has no way to include them.
        //
        //   Generate a single row with comma-separated column
        //   names, each within double quotes.
        for ( $column = 0; $column < $nColumns; $column++ )
        {
            $name = $table->getColumnName( $column );
            if ( $column != 0 )
                $text .= ",";
            $text .= '"' . $name . '"';
        }
        $text .= "\r\n";


        //
        // Encode rows
        // -----------------------------------------------------
        //   Output unquoted values for integers, floating-point
        //   values, booleans, and nulls. The rest are quoted.
        for ( $row = 0; $row < $nRows; $row++ )
        {
            $r = $table->getRowValues( $row );

            for ( $column = 0; $column < $nColumns; $column++ )
            {
                if ( $column != 0 )
                    $text .= ",";
                $v = $r[$column];
                if ( is_int( $v ) || is_float( $v ) ||
                    is_bool( $v ) || is_null( $v ) )
                    $text .= $v;
                else
                    $text .= '"' . $v . '"';
            }
            $text .= "\r\n";
        }

        return $text;
    }
    // @}
}

/**
 * @file
 * Defines SDSC\StructuredData\Format\FileNotFoundException to report that
 * a required file could not be found.
 */

namespace SDSC\StructuredData\Format;






/**
 * @class FileNotFoundException
 * FileNotFoundException describes an exception thrown when a required
 * file could not be opened, read, or written, depending upon the context.
 *
 *
 * @author  David R. Nadeau / University of California, San Diego
 *
 * @date    2/10/2016
 *
 * @since   0.0.1
 *
 * @version 0.0.1  Initial development.
 */
class FileNotFoundException
    extends FormatException
{
//----------------------------------------------------------------------
// Constructors & Destructors
//----------------------------------------------------------------------
    /**
     * @name Constructors
     */
    // @{
    /**
     * Constructs and returns a new exception object.
     *
     * @param string $message  the exception message
     *
     * @param int $code        the exception code
     *
     * @param int $severity    the severity level
     *
     * @param string $filename the filename where the exception was created
     *
     * @param int $lineno      the line where the exception was created
     *
     * @param Exception $previous the previous exception, if any
     */
    public function __construct(
        $message  = "",
        $code     = 0,
        $severity = 1,
        $filename = __FILE__,
        $lineno   = __LINE__,
        \Exception $previous = NULL )
    {
        parent::__construct( $message, $code, $severity,
            $filename, $lineno, $previous );
    }
    // @}

    /**
     * @name Destructors
     */
    // @{
    /**
     * Destroys a previously-constructed object.
     */
    public function __destruct( )
    {
        parent::__destruct( );
    }
    // @}
}


/**
 * @file
 * Defines SDSC\StructuredData\Format\FormatException to report format
 * encode and decode problems.
 */

namespace SDSC\StructuredData\Format;





/**
 * @class FormatException
 * FormatException describes an exception associated with format encode
 * or decode, such as for syntax errors and problems with content types
 * or organization.
 *
 *
 * @author  David R. Nadeau / University of California, San Diego
 *
 * @date    2/10/2016
 *
 * @since   0.0.1
 *
 * @version 0.0.1  Initial development.
 */
class FormatException
    extends \ErrorException
{
//----------------------------------------------------------------------
// Constructors & Destructors
//----------------------------------------------------------------------
    /**
     * @name Constructors
     */
    // @{
    /**
     * Constructs and returns a new exception object.
     *
     * @param string $message  the exception message
     *
     * @param int $code        the exception code
     *
     * @param int $severity    the severity level
     *
     * @param string $filename the filename where the exception was created
     *
     * @param int $lineno      the line where the exception was created
     *
     * @param Exception $previous the previous exception, if any
     */
    public function __construct(
        $message  = "",
        $code     = 0,
        $severity = 1,
        $filename = __FILE__,
        $lineno   = __LINE__,
        \Exception $previous = NULL )
    {
        parent::__construct( $message, $code, $severity,
            $filename, $lineno, $previous );
    }
    // @}

    /**
     * @name Destructors
     */
    // @{
    /**
     * Destroys a previously-constructed object.
     */
    public function __destruct( )
    {
        // Parent class has no __destruct method
    }
    // @}
}


/**
 * @file
 * Defines SDSC\StructuredData\Format\FormatRegistry with methods to
 * list known formats and intuit the format of an unknown file.
 */

namespace SDSC\StructuredData\Format;





// Known formats













/**
 * @class FormatRegistry
 * FormtRegistry is a static class that provides methods to list known
 * format encoders/decoders, find an encoder based on the type of data
 * to encode, and find a decoder capable of decoding a given file.
 *
 *
 * @author  David R. Nadeau / University of California, San Diego
 *
 * @date    2/17/2016
 *
 * @since   0.0.1
 *
 * @version 0.0.1  Initial development.
 */
final class FormatRegistry
{
//----------------------------------------------------------------------
// Fields
//----------------------------------------------------------------------
    /**
     * @var  array $formatsByName
     * An associative array of all formats where array keys are the
     * lower case format names (which should be unique) and array
     * values are format objects.
     */
    private static $formatsByName;

    /**
     * @var  array $formatsByComplexity
     * An array of 11 entries with keys 0..10 for the standard format
     * complexity levels, where array values are associative arrays
     * where array keys are the lower case format names and array
     * values are format objects.
     */
    private static $formatsByComplexity;

    /**
     * @var  boolean $initialized
     * True if the $formatsByName array has been initialized.
     */
    private static $initialized = FALSE;




//----------------------------------------------------------------------
// Constructors & Destructors
//----------------------------------------------------------------------
    /**
     * @name Constructors
     */
    // @{
    /**
     * Blocked constructor.
     *
     * Because private constructors cannot be tested in unit testing,
     * we mark this function as being ignored in code coverage reports.
     * @codeCoverageIgnore
     */
    private function __construct( )
    {
    }
    // @}

    /**
     * @name Destructors
     */
    // @{
    /**
     * Blocked constructor.
     *
     * Because private constructors cannot be tested in unit testing,
     * we mark this function as being ignored in code coverage reports.
     * @codeCoverageIgnore
     */
    private function __destruct( )
    {
    }
    // @}





//----------------------------------------------------------------------
// Format list operations
//----------------------------------------------------------------------
    /**
     * @name Format list operations
     */
    // @{
    /**
     * Initializes the format list with instances of known formats.
     */
    static private function initialize( )
    {
        // Create empty arrays.
        self::$formatsByName = array( );
        self::$formatsByComplexity = array( );
        for ( $i = 0; $i <= 10; ++$i )
            self::$formatsByComplexity[$i] = array( );

        // Add well-known formats.
        self::addFormat( new CSVTableFormat( ) );
        self::addFormat( new TSVTableFormat( ) );
        self::addFormat( new HTMLTableFormat( ) );
        self::addFormat( new JSONTableFormat( ) );
        self::addFormat( new JSONTreeFormat( ) );
        self::addFormat( new JSONGraphFormat( ) );

        ksort( self::$formatsByName );
        self::$initialized = TRUE;
    }



    /**
     * Adds a new format object to the list.
     *
     * Example:
     * @code
     *   FormatRegistry::addFormat( new MyFormat( ) );
     * @endcode
     *
     * @param AbstractFormat $format  the format to add.
     *
     * @throw \InvalidArgumentException if $format is not a subclass
     * of AbstractFormat.
     */
    static public function addFormat( $format )
    {
        // Validate.
        if ( $format == NULL ||
            !($format instanceof \SDSC\StructuredData\Format\AbstractFormat) )
            throw new \InvalidArgumentException(
                'Format argument must be an instance of AbstractFormat.' );

        // Add the format to the by-name array.
        $name = strtolower( $format->getName( ) );
        self::$formatsByName[$name] = $format;

        // Add the format to the by-complexity array.
        $c = $format->getComplexity( );
        if ( $c < 0 )
            $c = 0;
        else if ( $c > 10 )
            $c = 10;
        self::$formatsByComplexity[$c][$name] = $format;
    }
    // @}





//----------------------------------------------------------------------
// Format list methods
//----------------------------------------------------------------------
    /**
     * @name Format list methods
     */
    // @{
    /**
     * Finds a list of format objects that support the given file name
     * extension.
     *
     * Example:
     * @code
     *   $formatArray = FormatRegistry::findFormatsByExtension( "csv" );
     * @endcode
     *
     * @param   string  $extension  the file name extension, without a dot
     *
     * @return  array               an array of format objects that support
     * encoding or decoding files with the file name extension
     *
     * @throw \InvalidArgumentException if $extension is not a scalar string,
     * or it is empty.
     */
    static public function findFormatsByExtension( $extension )
    {
        if ( !is_scalar( $extension ) )
            throw new \InvalidArgumentException(
                'File name extension argument must be a scalar string.' );
        if ( empty( $extension ) )
            throw new \InvalidArgumentException(
                'File name extension argument must not be empty.' );

        // Since the registry is initialized only once per application run,
        // it is not possible for unit tests in a single application to test
        // every code path to initialize the registry.
        // @codeCoverageIgnoreStart
        if ( !self::$initialized )
            self::initialize( );
        // @codeCoverageIgnoreEnd

        // Make sure there is no leading dot.
        $ext = pathinfo( $extension, PATHINFO_EXTENSION );
        if ( empty( $ext ) )
            $ext = $extension;

        // Search for formats that support this extension.
        $results = array( );
        foreach ( self::$formatsByName as $format )
        {
            if ( $format->isFileExtension( $ext ) )
                $results[] = $format;
        }
        return $results;
    }

    /**
     * Finds a format object by name.
     *
     * Example:
     * @code
     *   $format = FormatRegistry::findFormatByName( 'CSV' );
     * @endcode
     *
     * @param string $name  the name of a format to look for
     *
     * @return AbstractFormat  the format object with the given name,
     * or a NULL if the name is not found.
     *
     * @throw \InvalidArgumentException if $name is not a scalar string,
     * or it is empty.
     */
    static public function findFormatByName( $name )
    {
        if ( !is_scalar( $name ) )
            throw new \InvalidArgumentException(
                'Format name argument must be a scalar string.' );
        if ( empty( $name ) )
            throw new \InvalidArgumentException(
                'Format name argument must not be empty.' );

        // Since the registry is initialized only once per application run,
        // it is not possible for unit tests in a single application to test
        // every code path to initialize the registry.
        // @codeCoverageIgnoreStart
        if ( !self::$initialized )
            self::initialize( );
        // @codeCoverageIgnoreEnd

        $name = strtolower( $name );
        if ( isset( self::$formatsByName[(string)$name] ) )
            return self::$formatsByName[(string)$name];
        return NULL;
    }

    /**
     * Returns a list of the names of all formats.
     *
     * Example:
     * @code
     *   $formatNames = FormatRegistry::getAllFormats( );
     *   foreach ( $formatNames as $name )
     *   {
     *     $format = FormatRegistry::findFormatByName( $name );
     *     ...
     *   }
     * @endcode
     *
     * @return array  an array containing the names of all formats, in
     * an arbitrary order.
     */
    static public function getAllFormats( )
    {
        // Since the registry is initialized only once per application run,
        // it is not possible for unit tests in a single application to test
        // every code path to initialize the registry.
        // @codeCoverageIgnoreStart
        if ( !self::$initialized )
            self::initialize( );
        // @codeCoverageIgnoreEnd

        return array_keys( self::$formatsByName );
    }



    /**
     * Returns a list of the names of all formats that can decode
     * a Graph.
     *
     * Example:
     * @code
     *   $formatNames = FormatRegistry::getAllGraphDecoders( );
     * @endcode
     *
     * @return array  an array containing the names of all format that
     * can decode a Graph, in arbitrary order.
     */
    static public function getAllGraphDecoders( )
    {
        // Since the registry is initialized only once per application run,
        // it is not possible for unit tests in a single application to test
        // every code path to initialize the registry.
        // @codeCoverageIgnoreStart
        if ( !self::$initialized )
            self::initialize( );
        // @codeCoverageIgnoreEnd

        $results = array( );
        foreach ( self::$formatsByName as &$format )
        {
            if ( $format->canDecodeGraphs( ) )
                $results[] = $format->getName( );
        }
        return $results;
    }

    /**
     * Returns a list of the names of all formats that can decode
     * a Table.
     *
     * Example:
     * @code
     *   $formatNames = FormatRegistry::getAllTableDecoders( );
     * @endcode
     *
     * @return array  an array containing the names of all format that
     * can decode a Table, in arbitrary order.
     */
    static public function getAllTableDecoders( )
    {
        // Since the registry is initialized only once per application run,
        // it is not possible for unit tests in a single application to test
        // every code path to initialize the registry.
        // @codeCoverageIgnoreStart
        if ( !self::$initialized )
            self::initialize( );
        // @codeCoverageIgnoreEnd

        $results = array( );
        foreach ( self::$formatsByName as &$format )
        {
            if ( $format->canDecodeTables( ) )
                $results[] = $format->getName( );
        }
        return $results;
    }

    /**
     * Returns a list of the names of all formats that can decode
     * a Tree.
     *
     * Example:
     * @code
     *   $formatNames = FormatRegistry::getAllTreeDecoders( );
     * @endcode
     *
     * @return array  an array containing the names of all format that
     * can decode a Tree, in arbitrary order.
     */
    static public function getAllTreeDecoders( )
    {
        // Since the registry is initialized only once per application run,
        // it is not possible for unit tests in a single application to test
        // every code path to initialize the registry.
        // @codeCoverageIgnoreStart
        if ( !self::$initialized )
            self::initialize( );
        // @codeCoverageIgnoreEnd

        $results = array( );
        foreach ( self::$formatsByName as &$format )
        {
            if ( $format->canDecodeTrees( ) )
                $results[] = $format->getName( );
        }
        return $results;
    }

    /**
     * Returns a list of the names of all formats that can encode
     * a Graph.
     *
     * Example:
     * @code
     *   $formatNames = FormatRegistry::getAllGraphEncoders( );
     * @endcode
     *
     * @return array  an array containing the names of all format that
     * can encode a Graph, in arbitrary order.
     */
    static public function getAllGraphEncoders( )
    {
        // Since the registry is initialized only once per application run,
        // it is not possible for unit tests in a single application to test
        // every code path to initialize the registry.
        // @codeCoverageIgnoreStart
        if ( !self::$initialized )
            self::initialize( );
        // @codeCoverageIgnoreEnd

        $results = array( );
        foreach ( self::$formatsByName as &$format )
        {
            if ( $format->canEncodeGraphs( ) )
                $results[] = $format->getName( );
        }
        return $results;
    }

    /**
     * Returns a list of the names of all formats that can encode
     * a Table.
     *
     * Example:
     * @code
     *   $formatNames = FormatRegistry::getAllTableEncoders( );
     * @endcode
     *
     * @return array  an array containing the names of all format that
     * can encode a Table, in arbitrary order.
     */
    static public function getAllTableEncoders( )
    {
        // Since the registry is initialized only once per application run,
        // it is not possible for unit tests in a single application to test
        // every code path to initialize the registry.
        // @codeCoverageIgnoreStart
        if ( !self::$initialized )
            self::initialize( );
        // @codeCoverageIgnoreEnd

        $results = array( );
        foreach ( self::$formatsByName as &$format )
        {
            if ( $format->canEncodeTables( ) )
                $results[] = $format->getName( );
        }
        return $results;
    }

    /**
     * Returns a list of the names of all formats that can encode
     * a Tree.
     *
     * Example:
     * @code
     *   $formatNames = FormatRegistry::getAllTreeEncoders( );
     * @endcode
     *
     * @return array  an array containing the names of all format that
     * can encode a Tree, in arbitrary order.
     */
    static public function getAllTreeEncoders( )
    {
        // Since the registry is initialized only once per application run,
        // it is not possible for unit tests in a single application to test
        // every code path to initialize the registry.
        // @codeCoverageIgnoreStart
        if ( !self::$initialized )
            self::initialize( );
        // @codeCoverageIgnoreEnd

        $results = array( );
        foreach ( self::$formatsByName as &$format )
        {
            if ( $format->canEncodeTrees( ) )
                $results[] = $format->getName( );
        }
        return $results;
    }
    // @}





//----------------------------------------------------------------------
// Decode methods
//----------------------------------------------------------------------
    /**
     * @name Decode methods
     */
    // @{
    /**
     * Parses a text string containing data and returns an array
     * containing one or more data objects.
     *
     * If the text is empty, an empty array is returned.
     *
     * Registered formats are checked to see which one(s), if any,
     * can decode the given text. If no format recognizes the text,
     * an empty array is returned. If a format recognizes the text,
     * but finds errors in its content, an exception is thrown
     * reporting those errors. Otherwise if a format recognizes the
     * text and parses it without errors, then an array of objects
     * parsed from the text is returned.
     *
     * Some registered formats have higher syntactic complexity
     * than others (e.g. XML is more complex than CSV). Because low
     * complexity formats may accept virtually any input, they are
     * a fall-back after checking high complexity formats first.
     *
     * Decoding the text requires a search through the format registry
     * to find the proper decoder. When the second argument is a
     * file name extension (without the leading dot), only those formats
     * that support the extension are tested for decoding the text.
     * When the extension string is empty (default), all formats in
     * the registry are tested.
     *
     * Example:
     * @code
     *   $objects = FormatRegistry::decode( $text );
     * @endcode
     *
     * @param  string  $text   a text string containing data to decode
     * into an array of returned data objects.
     *
     * @param  string  $extension a text string containing a filename
     * extension (without the leading dot) used to constrain the set of
     * formats to test for decoding the file. When the string is empty
     * (default), all formats are searched.
     *
     * @return array           returns an array containing objects
     * parsed from the text, or an empty array if no parsable data
     * was found.
     *
     * @throws FormatException  if the text could not be parsed
     * properly due to a variety of format-specific syntax and content
     * errors (see SyntaxException and InvalidContentException).
     */
    static public function decode( &$text, $extension = '' )
    {
        // Since the registry is initialized only once per application run,
        // it is not possible for unit tests in a single application to test
        // every code path to initialize the registry.
        // @codeCoverageIgnoreStart
        if ( !self::$initialized )
            self::initialize( );
        // @codeCoverageIgnoreEnd


        // Return an empty array if there is no content.
        if ( $text == NULL || empty( $text ) )
            return array( );

        // Run through the registered formats in high-to-low complexity
        // order. Check each one to see if it can parse the text.
        //
        // We have four cases to watch for:
        //
        //  - Clear success. The format parsed the input and returned a
        //    complex object, such as a tree, graph, or multi-column
        //    and multi-row table.
        //
        //  - Suspicious success. The format parsed the input and returned
        //    a very simple object, such as a table with one column.
        //    This can happen with very low complexity formats
        //    that can turn just about anything into a one-column table
        //    (e.g. CSV and TSV).
        //
        //  - Clear failure. The format returned nothing or threw a
        //    SyntaxException on the input, indicating that the input
        //    in no way matched the format's expected syntax.
        //
        //  - Probable failure. The format recognized the input as having
        //    valid syntax, but then threw an InvalidContentException
        //    when something went wrong.
        //
        // On clear success, the format search ends and the complex
        // content is returned.
        //
        // On suspicious success, the returned very simple object is
        // saved and the format search continues to see if any other
        // format can do better. If none can, the simple object is
        // returned.
        //
        // On clear failure, the thrown exception is saved and the
        // format search continues. If no format recognizes the input,
        // the last save extension is thrown.
        //
        // On probable failure, the thrown exception is saved and
        // the format search continues. If no other format recognizes
        // the input, the saved exception is thrown.
        $savedException = NULL;
        $savedResult    = NULL;
        $numberDecodes  = 0;
        for ( $complexity = 10; $complexity >= 0; --$complexity )
        {
            foreach ( self::$formatsByComplexity[$complexity] as &$format )
            {
                // If a file name extension was given, skip the format
                // unless it supports the extension.
                if ( !empty( $extension ) &&
                    !in_array( $extension, $format->getFileExtensions( ) ) )
                        continue;

                ++$numberDecodes;

                try
                {
                    // Try to decode the text.
                    $result = $format->decode( $text );

                    // An exception was not thrown, so we have one of:
                    //  - Clear failure if the results are empty.
                    //  - Clear success if the results are complex.
                    //  - Suspicious success if the results are simplistic.
                    //
                    // Suspicious simplistic content tends to be a Table
                    // with one column and everything in that column.  If
                    // the results look like this, put it asside and try
                    // other formats. For everything else, accept it as
                    // a success.

                    if ( empty( $result ) )
                        continue;               // Clear failure

                    if ( count( $result ) > 1 )
                        return $result;         // Clear success

                    $object = $result[0];
                    if ( !($object instanceof \SDSC\StructuredData\Table) )
                        return $result;         // Clear success

                    if ( $object->getNumberOfColumns( ) > 1 )
                        return $result;         // Clear success

                    // Otherwise suspicious success. Save results and try
                    // another format.  Clear any saved exception since
                    // simplistic results are better than failure.
                    if ( $savedResult == NULL )
                    {
                        $savedResult    = $result;
                        $savedException = NULL;
                    }
                }
                catch ( SyntaxException $e )
                {
                    // A syntax exception was thrown. The input's
                    // syntax was so bad that it couldn't be parsed.
                    if ( $savedResult == NULL )
                        $savedException = $e;
                    continue;                   // Clear failure
                }
                catch ( InvalidContentException $e )
                {
                    // An exception was thrown that the input had
                    // problems. The syntax was apparently OK, but
                    // the content didn't make sense.  It is probable,
                    // but not guaranteed, that this is a clear failure.
                    // Save the exception and try other formats to
                    // be sure.
                    //
                    // But skip saving the exception if we have saved
                    // simplistic results from earlier. Those results
                    // are better than failure.
                    if ( $savedResult == NULL )
                        $savedException = $e;
                    continue;                   // Probable failure
                }
            }
        }

        // If we've reached this point, then we never got clear success.
        // We have one of:
        //  - No decoder found
        //  - Probable failure with a saved exception
        //  - Suspicious success with saved results
        //  - Clear failure with a saved exception
        //
        // If no formats got checked because of file name extension
        // mismatches, return an empty array.
        //
        // With clear failure, throw the saved exception.
        //
        // With suspicious success, return the saved results.
        //
        // With probable failure, throw the saved exception.
        if ( $numberDecodes == 0 )
            return array( );                    // No decoder found

        if ( $savedException != NULL )
            throw $savedException;              // Probable failure
        if ( $savedResult != NULL )
            return $savedResult;                // Suspicious success

        return array( );                        // Clear failure
    }

    /**
     * Parses a file containing data and returns an array containing
     * one or more data objects, or an empty array if the file cannot
     * be parsed.
     *
     * If the file name is empty, or the file is empty, or the
     * file does not containing any content recognized by this
     * format, the method returns an empty array.  Otherwise, the
     * returned array contains one or more objects built from the
     * parsed file contents.
     *
     * Decoding the file requires a search through the format registry
     * to find the proper decoder. When the second argument is true,
     * the file's extension is used to select only those formats that
     * support the extension. When the second argument is false (default),
     * all formats are tested to find a suitable decoder.
     *
     * Example:
     * @code
     *   $objects = FormatRegistry::decodeFile( $filename, true );
     * @endcode
     *
     * @param  string  $filename  a text string containing the name
     * of a file containing data to decode into an array of returned
     * data objects.
     *
     * @param  boolean $useExtension  when true, the decoder extracts
     * the file's extension and uses it to select appropriate formats to
     * decode the file. When false (the default), the extension is
     * ignored and all registered formats are tested.
     *
     * @return array           returns an array containing objects
     * parsed from the text, or an empty array if no parsable data
     * was found or the file could not be parsed.
     *
     * @throws FormatException  if the text could not be parsed
     * properly due to a variety of format-specific syntax and content
     * errors (see SyntaxException and InvalidContentException).
     *
     * @throws FileNotFoundException  if the file could not be opened
     * or read, such as when the file or some directory on the file path
     * does not exist or is not readable.
     *
     * @throws \InvalidArgumentException  if the file could not be read,
     * such as due to permissions problems or system level errors.
     */
    static public function decodeFile( $filename, $useExtension = false )
    {
        // Check if the file is readable.
        if ( is_readable( $filename ) === FALSE )
            throw new FileNotFoundException(
                'File or directory not found or not readable',
                0, 1, $filename );

        // Extract the file name extension, if needed.
        $extension = '';
        if ( $useExtension )
        {
            $extension = pathinfo( $filename, PATHINFO_EXTENSION );

            // If there is no extension, we have to revert to searching
            // the entire registry for a suitable decoder.
            if ( $extension == NULL )
                $extension = '';
        }


        // Read the file's contents, catching file system errors.
        // On failure, file_get_contents issues an E_WARNING, which
        // we catch in the error handler.
        //
        // Because we've already determined that the file exists and
        // is readable, the kind of file system errors we'd get here
        // are obscure, such as if existence or permissions changed
        // suddenly, a file system became unmounted, a network file
        // system had network problems, etc.
        //
        // Because file system errors cannot be tested for in unit
        // testing, we mark this code to ignore it in code coverage
        // reports.
        // @codeCoverageIgnoreStart
        set_error_handler(
            function( $severity, $message, $file, $line )
            {
                throw new \InvalidArgumentException( $message, $severity );
            }
        );
        // @codeCoverageIgnoreEnd
        try
        {
            $text = file_get_contents( $filename );
        }
        // @codeCoverageIgnoreStart
        catch ( \Exception $e )
        {
            restore_error_handler( );
            throw $e;
        }
        restore_error_handler( );
        // @codeCoverageIgnoreEnd


        // Decode the text.
        return self::decode( $text, $extension );
    }
    // @}
}


/**
 * @file
 * Defines SDSC\StructuredData\Format\HTMLTableFormat to parse and
 * serialize data in the HTML text format.
 */

namespace SDSC\StructuredData\Format;


use SDSC\StructuredData\Table;





/**
 * @class HTMLTableFormat
 * HTMLTableFormat provides decode and encode functions that
 * map between HTML table text and a Table.
 *
 * HTML is a general-purpose document structure and content syntax
 * used for web pages. Those pages may include headings, paragraphs,
 * images, linkes, and... tables. This parser looks for tables in
 * the HTML, and parses and returns the selected table (defaulting
 * to the first table). An HTML table can have an arbitrary number
 * of rows and columns. All columns have a name. Row values may be
 * of any data type, though they are typically numeric.
 *
 *
 * ####Table syntax
 * An HTML table is delimted by `<table>...</table>`.  Between these,
 * column headings are usually included within `<thead>...</thead>`,
 * while table content is within `<tbody>...</tbody>`.
 *
 * Headings and content are divided into rows, delimited by
 * `<tr>...</tr>`. Each value in a row is delimited by either
 * `<th>...</th>` or `<td>...</td>`.
 *
 * This parser uses the first row in the `<thead>...</thead>` section
 * to use as the table's column names. All other heading rows are
 * ignored. If there are no heading rows, this parser uses the first
 * row in the `<tbody>...</tbody>` section for the column names.
 * All further body rows are used as table content.
 *
 * An optional `<caption>...</caption>` section's contents are used
 * as the table's short name. If there is no caption, the table's
 * name is left empty.
 *
 * HTML attributes are ignored, including `colspan`, `rowspan`, and
 * all styles, classes, IDs, etc. Table `<colgroup>...</colgroup>`
 * and `<col>...</col>` are ignored as these are used for styling and
 * provide no structure or content information.  Table
 * `<tfoot>...</tfoot>` are ignored.
 *
 * The following HTML table is parsed as having two columns and two
 * data rows:
 * <pre>
 *     &lt;table>
 *         &lt;head>
 *             &lt;tr>&lt;th>Temperature&lt;/th>&lt;th>Pressure&lt;/th>&lt;/tr>
 *         &lt;thead>
 *         &lt;tbody>
 *             &lt;r>&lt;td>123.4&lt;/td><&lt;d>567.8&lt;/td>&lt;/tr>
 *             &lt;r>&lt;td>901.2&lt;/td>&lt;td>345.6&lt;/td>&lt;/tr>
 *         &lt;tbody>
 *     &lt;table>
 * </pre>
 *
 *
 * #### Table decode limitations
 * HTML does not provide column descriptive information beyond column
 * names. The returned table uses these HTML names as column short names,
 * but leaves column long names, descriptions, and data types empty.
 *
 * HTML's table caption sets the table's short name. The returned table's
 * long name and description are left empty.
 *
 * Since the HTML syntax does not provide data types for column values,
 * these data types are automatically inferred by the
 * SDSC\StructuredData\Table class. That class scans through each column and
 * looks for a consistent interpretation of the values as integers,
 * floating-point numbers, booleans, etc., then sets the data type
 * accordingly.
 *
 *
 * #### Table encode limitations
 * The table's short name, if any, is used as the HTML table caption.
 * The table's long name and description are not included in the encoded
 * text.
 *
 * Since HTML only supports a single name for each column, the table's
 * column short names are output to the encoded text. The column
 * long names, descriptions, and data types are not included.
 *
 *
 * @see     SDSC\StructuredData\Table   the StructuredData Table class
 *
 * @author  David R. Nadeau / University of California, San Diego
 *
 * @date    1/27/2016
 *
 * @since   0.0.1
 *
 * @version 0.0.1  Initial development.
 *
 * @version 0.0.2  Revised to provide format attributes per RDA, and to
 * create tables using the updated Table API that uses an array of attributes.
 */
final class HTMLTableFormat
    extends AbstractFormat
{
//----------------------------------------------------------------------
// Constructors & Destructors
//----------------------------------------------------------------------
    /**
     * @name Constructors
     */
    // @{
    /**
     * Constructs and returns a new format object that may be used to
     * decode and encode tables in HTML.
     */
    public function __construct( )
    {
        parent::__construct( );

        $this->attributes['syntax']         = 'HTML';
        $this->attributes['name']           = 'HTML-Table';
        $this->attributes['longName']       = 'Hypertext Markup Language (HTML) Table';
        $this->attributes['MIMEType']       = 'text/html';
        $this->attributes['fileExtensions'] = array( 'htm', 'html' );
        $this->attributes['description'] =
            'The HTML (Hyper-Text Markup Language) format encodes ' .
            'documents with multiple headings, body text, tables, and ' .
            'images. Tabular data may have an unlimited number of rows ' .
            'and columns. Each column has a short name. All rows have a ' .
            'value for every column. Row values are typically integers ' .
            'or floating-point numbers, but they also may be strings and ' .
            'booleans.';
        $this->attributes['expectedUses'] = array(
            'Tabular data with named columns and rows of values' );
        $this->attributes['standards'] = array(
            array(
                'issuer' => 'W3C',
                'name' => 'HTML5',
                'natureOfApplicability' => 'specifies',
                'details' => 'A vocabulary and associated APIs for HTML and XHTML'
            )
        );
        $this->attributes['creationDate']         = '2014-10-28 00:00:00';
        $this->attributes['lastModificationDate'] = '2014-10-28 00:00:00';

        $this->attributes['contributors'] = array(
            array(
                'name'            => 'Ian Hickson',
                'details'         => 'Google, Inc.',
                'identifiedUsing' => 'Text'
            ),
            array(
                'name'            => 'Robin Berjon',
                'details'         => 'W3C',
                'identifiedUsing' => 'Text'
            ),
            array(
                'name'            => 'Steve Faulkner',
                'details'         => 'The Paciello Group',
                'identifiedUsing' => 'Text'
            ),
            array(
                'name'            => 'Travis Leithead',
                'details'         => 'Microsoft Corporation',
                'identifiedUsing' => 'Text'
            ),
            array(
                'name'            => 'Erika Doyle Navara',
                'details'         => 'Microsoft Corporation',
                'identifiedUsing' => 'Text'
            ),
            array(
                'name'            => 'Edward O\'Conner',
                'details'         => 'Apple Inc.',
                'identifiedUsing' => 'Text'
            ),
            array(
                'name'            => 'Silvia Pfeiffer',
                'identifiedUsing' => 'Text'
            )
        );

        // Unknown:
        //  identifier
    }
    // @}

    /**
     * @name Destructors
     */
    // @{
    /**
     * Destroys a previously-constructed format object.
     */
    public function __destruct( )
    {
        parent::__destruct( );
    }
    // @}





//----------------------------------------------------------------------
// Encode/decode attributes methods
//----------------------------------------------------------------------
    /**
     * @name Encode/decode attributes methods
     */
    // @{
    /**
     * @copydoc AbstractFormat::getComplexity
     */
    public function getComplexity( )
    {
        return 5;
    }

    /**
     * @copydoc AbstractFormat::canDecodeTables
     */
    public function canDecodeTables( )
    {
        return true;
    }

    /**
     * @copydoc AbstractFormat::canEncodeTables
     */
    public function canEncodeTables( )
    {
        return true;
    }
    // @}





//----------------------------------------------------------------------
// Encode/decode methods
//----------------------------------------------------------------------
    /**
     * @name Encode/decode methods
     */
    // @{
    /**
     * @copydoc AbstractFormat::decode
     *
     * #### Decode limitations
     * The HTML format always returns an array containing zero or more
     * SDSC\StructuredData\Table objects.
     */
    public function decode( &$text )
    {
        if ( empty( $text ) )
                return array( );        // No table

        //
        // Parse
        // -----------------------------------------------------
        // Parse the HTML text into a DOM document.  If any error
        // occurs, reject the content.
        $oldSetting = libxml_use_internal_errors( true );

        $doc = new \DOMDocument( );
        $doc->loadHTML( $text );

        $errors = libxml_get_errors( );
        if ( count( $errors ) > 0 )
        {
            // One or more errors occurred. $errors holds a list of
            // libXMLError objects that each have a level, code, column,
            // message, file, and line.
            //
            // We forward the message for the first error.
            libxml_use_internal_errors( $oldSetting );
            throw new SyntaxException( $errors[0]->message );
        }
        libxml_use_internal_errors( $oldSetting );


        //
        // Find and decode the tables
        // -----------------------------------------------------
        $tableNodes = $doc->getElementsByTagName( 'table' );
        $tables = array( );
        for ( $tableIndex = 0; $tableIndex < $tableNodes->length; $tableIndex++ )
        {
            $tableNode = $tableNodes->item( $tableIndex );


            //
            // Get caption as table name
            // -----------------------------------------------------
            //   Look for a <caption> as the table name.
            $tableName = NULL;
            $captionNodes = $tableNode->getElementsByTagName( 'caption' );
            if ( $captionNodes->length > 0 )
                $tableName = $captionNodes[0]->nodeValue;


            //
            // There are multiple variations we need to handle.
            //
            // 1. A correct table has column names in a <thead> and
            // table rows in a <tbody>
            //  <table>
            //      <thead>
            //          <tr><td>Name1</td><td>...</td></tr>
            //      </thead>
            //      <tbody>
            //          <tr><td>Value1</td><td>...</td></tr>
            //      </tbody>
            //  </table>
            //
            // 2. There may be no <thead>. The first row in the <tbody>
            // has the column names:
            //  <table>
            //      <tbody>
            //          <tr><td>Name1</td><td>...</td></tr>
            //          <tr><td>Value1</td><td>...</td></tr>
            //      </tbody>
            //  </table>
            //
            // 3. There may be no <tbody>, with rows just given in
            // the table:
            //  <table>
            //      <tr><td>Name1</td><td>...</td></tr>
            //      <tr><td>Value1</td><td>...</td></tr>
            //  </table>
            //
            // 4. There may be a <thead> for the column names, but
            // the rest of the table's rows are not in a <tbody>:
            //  <table>
            //      <thead>
            //          <tr><td>Name1</td><td>...</td></tr>
            //      </thead>
            //      <tr><td>Value1</td><td>...</td></tr>
            //  </table>
            //
            // In all cases, we treat <th> and <td> the same, ignore
            // <tfoot> for a footer, and ignore <colgroup> and <col>,
            // which are primarily for column formatting. We also
            // ignore all attributes (such as colspan).
            //
            // For column names in the <thead>, we only use the first
            // row.

            //
            // Get column names
            // -----------------------------------------------------
            //   Look through all <thead>s (should be zero or one) and
            //   all <tr>s in those <thead>s. Use the first <tr>'s
            //   <th> or <td> elements as column names.
            $columnNames = array( );
            $headNodes = $tableNode->getElementsByTagName( 'thead' );
            foreach ( $headNodes as $headNode )
            {
                // Get the <tr>s in this <thead>
                $trNodes = $headNode->getElementsByTagName( 'tr' );
                foreach ( $trNodes as $trNode )
                {
                    // Use <td> or <th> children of the <tr>
                    // as column names.
                    $children = $trNode->childNodes;
                    foreach ( $children as $child )
                    {
                        if ( $child->nodeName == 'td' ||
                            $child->nodeName == 'th' )
                            $columnNames[] = $child->nodeValue;
                    }

                    // If we found column names, stop this.
                    if ( !empty( $columnNames ) )
                        break;
                }

                // If we found column names, stop this.
                if ( !empty( $columnNames ) )
                    break;
            }

            // At this point, we may have found column names in a
            // <thead>, or we may not have. Move on to look for
            // rows of data.
            

            //
            // Get rows
            // -----------------------------------------------------
            //   Collect all <tr>s. Ignore those that aren't direct
            //   children of the <table> or a <tbody> child of the
            //   <table>. This will skip <tr>s in <thead> or <tfoot>,
            //   and any <tr>s in nested tables.
            $rows = array( );
            $trNodes = $tableNode->getElementsByTagName( 'tr' );
            foreach ( $trNodes as $trNode )
            {
                // Ignore this <tr> unless its parent is a <tbody>
                // or the <table>. This eliminates <tr>s in <thead>
                // and <tfoot>.
                if ( $trNode->parentNode->nodeName != 'table' &&
                    $trNode->parentNode->nodeName != 'tbody' )
                    continue;

                // Ignore this <tr> unless its parent is the table
                // we're parsing, or unless its parent is a <tbody>
                // and that node's parent is the table.
                if ( $trNode->parentNode->nodeName == 'table' &&
                    $trNode->parentNode !== $tableNode )
                    continue;
                if ( $trNode->parentNode->nodeName == 'tbody' &&
                    $trNode->parentNode->parentNode !== $tableNode )
                    continue;

                // Collect the <tr> node's children <tr> or <th>
                // nodes and use their values as row values.
                // This ignores any nested HTML.
                $children = $trNode->childNodes;
                if ( $children->length > 0 )
                {
                    // Create a row from the <th> and
                    // <td> nodes
                    $row = array( );
                    foreach ( $children as $child )
                    {
                        if ( $child->nodeName == 'td' ||
                            $child->nodeName == 'th' )
                            $row[] = $child->nodeValue;
                    }
                    $rows[] = $row;
                }
            }

            // If there was no <thead> earlier, use the first
            // table row for the column names. If there are no rows,
            // though, then we have no columns or rows, and thus no
            // table.
            if ( count( $columnNames ) == 0 )
            {
                if ( count( $rows ) <= 0 )
                    continue;           // No rows or columns! No table.
                $columnNames = array_shift( $rows );
            }


            // Build table
            // -----------------------------------------------------
            $attributes = array(
                // 'name' perhaps unknown
                // 'longName' unknown
                // 'description' unknown
                // 'sourceFileName' unknown
                'sourceMIMEType'   => $this->getMIMEType( ),
                'sourceSyntax'     => $this->getSyntax( ),
                'sourceSchemaName' => $this->getName( )
            );
            if ( $tableName != NULL )
                $attributes['name'] = $tableName;
            $table = new Table( $attributes );


            //
            // Add columns
            // -----------------------------------------------------
            //   Header provides column names.
            //   No column descriptions or data types.
            foreach ( $columnNames as &$name )
                $table->appendColumn( array( 'name' => $name ) );


            // Convert values rows
            // -----------------------------------------------------
            //   So far, every value in every row is a string. But
            //   we'd like to change to the "best" data type for
            //   the value. If it is an integer, make it an integer.
            //   If it is a float, make it a double. If it is a
            //   boolean, make it a boolean. Only fall back to string
            //   types if nothing better will do.
            foreach ( $rows as &$row )
            {
                foreach ( $row as $key => &$value )
                {
                    // Ignore any value except a string. But really,
                    // they should all be strings so we're just being
                    // paranoid.
                    // @codeCoverageIgnoreStart
                    if ( !is_string( $value ) )
                        continue;
                    // @codeCoverageIgnoreEnd

                    $lower = strtolower( $value );
                    if ( is_numeric( $value ) )
                    {
                        // Convert to float or int.
                        $fValue = floatval( $value );
                        $iValue = intval( $value );

                        // If int and float same, then must be an int
                        if ( $fValue == $iValue )
                            $row[$key] = $iValue;
                        else
                            $row[$key] = $fValue;
                    }
                    else if ( $lower === 'true' )
                        $row[$key] = true;
                    else if ( $lower === 'false' )
                        $row[$key] = false;

                    // Otherwise leave it as-is.
                }
            }


            // Add rows
            // -----------------------------------------------------
            //   Parsed content provides rows.
            if ( count( $rows ) != 0 )
            {
                try
                {
                    $table->appendRows( $rows );
                }
                catch ( \InvalidArgumentException $e )
                {
                    throw new InvalidContentException( $e->getMessage( ) );
                }
            }
            $tables[] = $table;
        }
        if ( empty( $tables ) )
            return NULL;
        return $tables;
    }





    /**
     * @copydoc AbstractFormat::encode
     *
     * #### Encode limitations
     * The HTML format supports encoding multiple
     * SDSC\StructuredData\Table objects in the format. An exception is thrown
     * if the $objects argument is not an array or contains an object that
     * is not a Table.
     */
    public function encode( &$objects, $options = '' )
    {
        //
        // Validate arguments
        // -----------------------------------------------------
        if ( empty( $objects ) )
            return NULL;            // No table to encode
        if ( !is_array( $objects ) )
            throw new \InvalidArgumentException(
                'HTML encode requires an array of objects.' );

        //
        // Encode all table objects
        // -----------------------------------------------------
        $text = '';
        foreach ( $objects as &$object )
        {
            if ( !is_a( $object, 'SDSC\StructuredData\Table', false ) )
                throw new \InvalidArgumentException(
                    'HTML encode object must be a table.' );

            $nColumns = $object->getNumberOfColumns( );
            if ( $nColumns <= 0 )
                continue;
            $nRows    = $object->getNumberOfRows( );
            $text     = "<table>\n";


            //
            // Encode caption
            // -----------------------------------------------------
            //  If there is a table name, use it as the caption.
            $tableName = $object->getName( );
            if ( !empty( $tableName ) )
                $text .= "  <caption>$tableName</caption>\n";


            //
            // Encode header
            // -----------------------------------------------------
            //   Generate a single row with comma-separated column
            //   names.
            $text .= "  <thead>\n";
            $text .= '    <tr>';
            for ( $column = 0; $column < $nColumns; $column++ )
            {
                $text .= '<th>' .
                    $object->getColumnName( $column ) .
                    '</th>';
            }
            $text .= "</tr>\n";
            $text .= "  </thead>\n";


            //
            // Encode rows
            // -----------------------------------------------------
            $text .= "  <tbody>\n";
            for ( $row = 0; $row < $nRows; $row++ )
            {
                $r = $object->getRowValues( $row );

                $text .= '    <tr>';
                for ( $column = 0; $column < $nColumns; $column++ )
                {
                    $text .= '<td>' . $r[$column] . '</td>';
                }
                $text .= "</tr>\n";
            }
            $text .= "  </tbody>\n</table>\n";
        }
        if ( empty( $text ) )
            return NULL;

        return $text;
    }
    // @}
}

/**
 * @file
 * Defines SDSC\StructuredData\Format\InvalidContentException to report that
 * parsed content has an invalid structure or content.
 */

namespace SDSC\StructuredData\Format;






/**
 * @class InvalidContentException
 * InvalidContentException describes an exception thrown when an error
 * occurs while trying to interpret parsed content based upon expected
 * structure or content rules.
 *
 * Typical content exceptions include:
 * - NULL or empty value
 * - Missing value
 * - Bad data type
 * - Bad structure
 *
 *
 * @author  David R. Nadeau / University of California, San Diego
 *
 * @date    2/10/2016
 *
 * @since   0.0.1
 *
 * @version 0.0.1  Initial development.
 */
class InvalidContentException
    extends FormatException
{
//----------------------------------------------------------------------
// Constructors & Destructors
//----------------------------------------------------------------------
    /**
     * @name Constructors
     */
    // @{
    /**
     * Constructs and returns a new exception object.
     *
     * @param string $message  the exception message
     *
     * @param int $code        the exception code
     *
     * @param int $severity    the severity level
     *
     * @param string $filename the filename where the exception was created
     *
     * @param int $lineno      the line where the exception was created
     *
     * @param Exception $previous the previous exception, if any
     */
    public function __construct(
        $message  = "",
        $code     = 0,
        $severity = 1,
        $filename = __FILE__,
        $lineno   = __LINE__,
        \Exception $previous = NULL )
    {
        parent::__construct( $message, $code, $severity,
            $filename, $lineno, $previous );
    }
    // @}

    /**
     * @name Destructors
     */
    // @{
    /**
     * Destroys a previously-constructed object.
     */
    public function __destruct( )
    {
        parent::__destruct( );
    }
    // @}
}


/**
 * @file
 * Defines SDSC\StructuredData\Format\JSONGraphFormat to parse and
 * serialize data in the JSON (JavaScript Object Notation) text syntax
 * for graphs.
 */

namespace SDSC\StructuredData\Format;


use SDSC\StructuredData\Graph;





/**
 * @class JSONGraphFormat
 * JSONGraphFormat provides decode and encode functions that map
 * between JSON (JavaScript Object Notation) text and a
 * \SDSC\StructuredData\Graph.
 *
 * JSON is a general-purpose syntax for describing objects, arrays,
 * scalars, and arrays of objects, arrays, of scalars to an arbitrary
 * nesting depth. This class, however, focuses on a narrower subset of
 * JSON usage in order to build graphs.
 *
 *
 * #### Graph syntax
 * A JSON graph is a hierarchy of nodes starting with a root node
 * with a name and list of children. Each of those children nodes
 * has a name and their own list of children, and so on to arbitrary
 * depth. Any node can have any number of named attributes with
 * arbitrary values.
 *
 *
 * ##### Graph object
 * JSON graphs always start as one of two types of object:
 * - A single graph
 * - An array of graphs
 *
 * For an array of graphs, the top-level object is expected to have
 * a "label" attribute that names the array, a "type" that characterizes
 * the array of graphs, and a "metadata" object of additional attributes.
 * A "graphs" attribute contains an array of the individual graphs.
 * <pre>
 *   {
 *     "label": "My list of graphs",
 *     "type":  "Supercool",
 *     "metadata": { ... },
 *     "graphs": [ ... ]
 *   }
 * </pre>
 *
 * A top-level single graph, or a graph in a graph array, is an object
 * that is expected to have a "label" attribute that names the graph,
 * a "type" attribute that characterizes the graph, a "directed" attribute
 * indicates if the graph is directed, and a "metadata" object
 * of additional attributes. The graph then has two arrays named "nodes"
 * and "edges":
 * <pre>
 *   {
 *     "label": "My graph",
 *     "type":  "Exciting!",
 *     "directed": true,
 *     "metadata": { ... },
 *     "nodes": [ ... ],
 *     "edges": [ ... ]
 *   }
 * </pre>
 *
 * Each entry in the "nodes" array describes a single node. Each node
 * has a "label" that names the node, an "id" that gives the node's
 * unique ID (typically a number), and a "metadata" object of additional
 * attributes.
 * <pre>
 *   {
 *     "label": "My node",
 *     "id": "1",
 *     "metadata": { ... }
 *   }
 * </pre>
 *
 * Each entry in the "edges" array describes a single edge between two
 * nodes. Each edge has a "label" that names the edge, a "relation"
 * that characterizes the edge, a boolean "directed" flag, a "metadata"
 * object of additional attributes, and "source" and "target" properties that
 * give the unique IDs of the nodes on either end of the edge.
 * <pre>
 *   {
 *     "label": "My edge",
 *     "relation": "connects-to",
 *     "directed": true,
 *     "metadata": { ... },
 *     "source": "1",
 *     "target": "2"
 *   }
 * </pre>
 *
 * ##### Graph types
 * The "type" attribute for graph arrays and individual graphs has no
 * defined vocabulary.
 *
 * ##### Edge relations
 * The "relation" attribute for edges has no defined vocabulary.
 *
 * ##### Metadata
 * The "metadata" attribute for graph arrays, graphs, nodes, and edges,
 * is an object with named values, but the names have no defined vocabulary.
 *
 * ##### Node IDs
 * The "id" attribute for nodes must have a unique value for every node,
 * but the structure of that value is not defined.
 *
 *
 * ##### Graph schema name
 * JSON graphs can have a microformat schema name that refers to
 * a well-known schema by setting the "type" attribute of the parent
 * object.  The type attribute value may be an array or a scalar with a
 * single string value.
 * <pre>
 *  {
 *      "type": [ "json-graph" ],
 *      "graph": [ ... ]
 *  }
 * </pre>
 *
 * This "type" attribute is semi-standard for microformat schemas, but
 * it collides with the "type" attribute for arrays of graphs and graphs.
 * However, since the vocabulary for "type" is not defined anyway for
 * graphs, this overload is acceptable.
 *
 *
 * #### Graph decode limitations
 * The amount of graph and node descriptive information available
 * in a JSON file depends upon how much of syntax above is used.
 * While graphs and nodes should have names, for instance, these are
 * optional. Descriptions and other metadata are also optional.
 *
 * When an array of graphs is read, each of the individual graphs are
 * returned by the decode method. Attributes for the array itself are
 * ignored. Only attributes for individual graphs are returned.
 *
 *
 * #### Graph encode limitations
 * The encoder can output a single graph or an array of graphs.
 *
 *
 * @see     SDSC\StructuredData\Graph    the StructuredData Graph class
 *
 * @author  David R. Nadeau / University of California, San Diego
 *
 * @date    2/15/2016
 *
 * @since   0.0.1
 *
 * @version 0.0.1  Initial development.
 */
final class JSONGraphFormat
    extends AbstractFormat
{
//----------------------------------------------------------------------
// Constants
//----------------------------------------------------------------------
    /**
     * An encoding style that generates a single object that starts
     * immediately with the graph. This is the most basic form
     * of graph output and omits the schema name, but includes all
     * nodes and edges and their attributes.
     *
     * <pre>
     *   {
     *     "label": "My graph",
     *     "type":  "Exciting!",
     *     "metadata": { ... },
     *     "nodes": [ ... ],
     *     "edges": [ ... ]
     *   }
     * </pre>
     */
    const ENCODE_AS_OBJECT = 1;

    /**
     * An encoding style identical to ENCODE_AS_OBJECT, but with
     * a parent object that includes an array of individual graphs
     * and a schema type.
     *
     * This is the default encoding.
     *
     * <pre>
     * {
     *   "type": "json-graph",
     *   "graphs": [
     *     {
     *       "label": "My graph",
     *       "type":  "Exciting!",
     *       "metadata": { ... },
     *       "nodes": [ ... ],
     *       "edges": [ ... ]
     *     }
     *   ]
     * }
     * </pre>
     */
    const ENCODE_AS_OBJECT_WITH_SCHEMA = 2;





//----------------------------------------------------------------------
// Constructors & Destructors
//----------------------------------------------------------------------
    /**
     * @name Constructors
     */
    // @{
    /**
     * Constructs and returns a new format object that may be used to
     * decode and encode graphs in JSON (JavaScript Object Notation).
     */
    public function __construct( )
    {
        parent::__construct( );

        $this->attributes['syntax']         = 'JSON';
        $this->attributes['name']           = 'json-graph';
        $this->attributes['longName']       = 'JavaScript Object Notation (JSON) Graph';
        $this->attributes['MIMEType']       = 'application/json';
        $this->attributes['fileExtensions'] = array( 'json' );
        $this->attributes['description'] =
            'The JSON (JavaScript Object Notation) format encodes ' .
            'a variety of data, including tables, graphs, and graphs. '.
            'Graph data may have an unlimited number of nodes connected ' .
            'by edges to create an arbitrarily complex structure.  Each ' .
            'node and edge may have a short name, long name, and ' .
            'description.';
        $this->attributes['expectedUses'] = array(
            'Graphs with nodes and edges with names and values'
        );
        $this->attributes['standards'] = array(
            array(
                'issuer' => 'RFC',
                'name' => 'IETF RFC 7159',
                'natureOfApplicability' => 'specifies',
                'details' => 'The JavaScript Object Notation (JSON) Data Interchange Format'
            ),
            array(
                'issuer' => 'ad hoc',
                'name' => 'JSON Graph',
                'natureOfApplicability' => 'specifies',
                'details' => ''
            )
        );

        // Unknown:
        //  identifier
        //  creationDate
        //  lastModificationDate
        //  contributors
    }
    // @}

    /**
     * @name Destructors
     */
    // @{
    /**
     * Destroys a previously-constructed format object.
     */
    public function __destruct( )
    {
        parent::__destruct( );
    }
    // @}





//----------------------------------------------------------------------
// Encode/decode attribute methods
//----------------------------------------------------------------------
    /**
     * @name Encode/decode attribute methods
     */
    // @{
    /**
     * @copydoc AbstractFormat::getComplexity
     */
    public function getComplexity( )
    {
        return 10;
    }

    /**
     * @copydoc AbstractFormat::canDecodeGraphs
     */
    public function canDecodeGraphs( )
    {
        return true;
    }

    /**
     * @copydoc AbstractFormat::canEncodeGraphs
     */
    public function canEncodeGraphs( )
    {
        return true;
    }
    // @}





//----------------------------------------------------------------------
// Encode methods
//----------------------------------------------------------------------
    /**
     * @name Encode methods
     */
    // @{
    /**
     * @copydoc AbstractFormat::decode
     *
     * #### Decode limitations
     * The JSON format always returns an array containing a single
     * SDSC\StructuredData\Graph object.
     */
    public function decode( &$text )
    {
        if ( empty( $text ) )
            return array( );        // No graph


        // Parse JSON
        // -----------------------------------------------------
        // Passing 'false' to json_decode( ) means that it should *not*
        // silently convert objects into arrays. We need to know whether
        // something in the text is an object or array because they have
        // different meanings and different parse paths below.
        $content = json_decode( $text, false );
        if ( $content == NULL )
        {
            // Failure to parse.
            $code = json_last_error( );
            switch ( $code )
            {
            case JSON_ERROR_STATE_MISMATCH:
            case JSON_ERROR_SYNTAX:
                throw new SyntaxException(
                    'Malformed JSON. Problem with commas, brackets, or parenthesis?' );
            case JSON_ERROR_CTRL_CHAR:
            case JSON_ERROR_UTF8:
                throw new SyntaxException(
                    'Malformed JSON. Control characters or bad UTF-8?' );

            // The maximum nesting depth is not defined by PHP and may
            // vary with changes in the implementation. This makes unit
            // testing for this case is not practical, so we ignore it.
            // @codeCoverageIgnoreStart
            case JSON_ERROR_DEPTH:
                throw new SyntaxException(
                    'Malformed JSON. Nesting too deep.' );
            // @codeCoverageIgnoreEnd
            }

            // There is no content, and yet we don't know what the
            // error is.
            throw new SyntaxException(
                'Malformed JSON.' );
        }
        // At this point we don't know what type of content we have.
        // We could have a graph.


        // Determine content type
        // -----------------------------------------------------
        // If the content is an object, look for a few
        // tell-tale properties to see what we have.
        if ( is_object( $content ) )
        {
            if ( property_exists( $content, 'graphs' ) )
            {
                // When there's a 'graphs' property, we have a top-level
                // object with an array of graphs. The top-level object
                // may have a schema.
                return $this->_decodeGraphObjectsWithSchema( $content );
            }
            if ( property_exists( $content, 'nodes' ) )
            {
                // When there's a 'nodes' property, we have a top-level
                // object that is a single graph and there is no schema.
                return array( $this->_decodeGraphObject( $content ) );
            }
        }

        // Otherwise we don't know what it is.
        throw new SyntaxException(
            'Unrecognized JSON content. Does not appear to be a graph.' );
    }

    /**
     * Decodes an array of graph objects with a schema.
     *
     * @param array $content  the content
     *
     * @throws InvalidContentException if the content cannot be parsed
     */
    private function _decodeGraphObjectsWithSchema( &$content )
    {
        // Check the type
        // -----------------------------------------------------
        // The format has a schema type and a list of graphs.
        // The schema type must be recognized.
        //
        // Good example:
        //  {
        //      "type":   [ "json-graph" ],
        //      "graphs": [ ... ]
        //  }

        if ( property_exists( $content, 'type' ) )
        {
            $type = $content->type;
            if ( !is_scalar( $type ) || (string)$type != 'json-graph' )
                throw new InvalidContentException(
                    'JSON graph "type" must be "json-graph".' );
        }


        // Parse, but ignore attributes
        // -----------------------------------------------------
        // A list of graphs can have a label and metadata. These
        // have a specific syntax, but we have no way to store them.
        // Nevertheless, enforce the syntax.
        $unusedAttributes = $this->_decodeAttributes( $content );


        // Create graphs
        // -----------------------------------------------------
        // Each entry in the "graphs" property (which has already
        // been checked and confirmed to exist) must be a valid
        // graph.
        $graphs = $content->graphs;
        if ( !is_array( $graphs ) )
            throw new InvalidContentException(
                'JSON "graphs" property must be an array of graph objects.' );

        $results = array( );
        foreach ( $graphs as &$graph )
        {
            if ( !is_object( $graph ) )
                throw new InvalidContentException(
                    'JSON "graphs" property must contain graph objects.' );
            $results[] = $this->_decodeGraphObject( $graph );
        }

        return $results;
    }

    /**
     * Decodes a graph object in the JSON Graph format.
     *
     * @param array $content  the content
     *
     * @throws InvalidContentException if the content cannot be parsed
     */
    private function _decodeGraphObject( &$content )
    {
        // Create graph
        // -----------------------------------------------------
        // The format supports a "label", which we use as the
        // graph "name". We also parse optional "metadata" for
        // additional well-known and custom graph attributes.
        //
        // Good example:
        //  {
        //      "label":  "my graph",
        //      "metadata": {
        //        "this": "that"
        //      }
        //      "nodes":  [ ... ],
        //      "edges":  [ ... ]
        //  }
        $attributes = array(
            // 'name' unknown
            // 'longName' unknown
            // 'description' unknown
            // 'sourceFileName' unknown
            'sourceMIMEType'   => $this->getMIMEType( ),
            'sourceSyntax'     => $this->getSyntax( ),
            'sourceSchemaName' => 'json-graph'
        );
        $graph = new Graph( $attributes );


        // Parse attributes
        // -----------------------------------------------------
        // Get more graph attributes, which may include the graph's
        // name and other metadata.
        //
        // The returned array also includes 'nodes' and 'edges',
        // if any.
        $moreAttributes = $this->_decodeAttributes( $content );


        // Parse nodes
        // -----------------------------------------------------
        // The node IDs in the file are needed to identify source
        // and target nodes for edges, but the file's node IDs
        // are not our internal IDs. So we need to maintain a mapping.
        $nodeIDMap = array( );
        if ( isset( $moreAttributes['nodes'] ) )
        {
            $nodes = $moreAttributes['nodes'];
            unset( $moreAttributes['nodes'] );
            if ( !is_array( $nodes ) )
                    throw new InvalidContentException(
                        'JSON "nodes" property must be an array of nodes.' );
            foreach ( $nodes as &$node )
            {
                // Parse the node's attributes. This must include
                // an 'id' attribute.
                $attr = $this->_decodeAttributes( $node );
                if ( !isset( $attr['id'] ) )
                    throw new InvalidContentException(
                        'JSON nodes must have an "id" property.' );

                // Get the node's ID in the file, then remove it from
                // the attributes we'll be saving for the new node.
                $fileID = $attr['id'];
                if ( !is_scalar( $fileID ) )
                    throw new InvalidContentException(
                        'JSON node "id" property must be a scalar string.' );
                unset( $attr['id'] );

                // Create the new node with the remaining attriutes.
                $memoryID = $graph->addNode( $attr );

                // Add to the ID map for when we handle edges.
                $nodeIDMap[(string)$fileID] = $memoryID;
            }
        }


        // Parse edges
        // -----------------------------------------------------
        // The edges reference nodes by their IDs in the file.
        // Since the file IDs are not the same as our internal IDs,
        // we need to map them as we process each edge.
        if ( isset( $moreAttributes['edges'] ) )
        {
            $edges = $moreAttributes['edges'];
            unset( $moreAttributes['edges'] );
            if ( !is_array( $edges ) )
                    throw new InvalidContentException(
                        'JSON "edges" property must be an array of nodes.' );
            foreach ( $edges as &$edge )
            {
                // Parse the edge's attributes. This must include
                // 'source' and 'target' attributes.
                $attr = $this->_decodeAttributes( $edge );
                $fileNode1 = -1;
                $fileNode2 = -1;
                if ( isset( $attr['source'] ) )
                {
                    if ( !is_scalar( $attr['source'] ) )
                        throw new InvalidContentException(
                            'JSON edge "source" must be a scalar string.' );
                    $fileNode1 = (integer)$attr['source'];
                    unset( $attr['source'] );
                }
                if ( isset( $attr['target'] ) )
                {
                    if ( !is_scalar( $attr['target'] ) )
                        throw new InvalidContentException(
                            'JSON edge "source" must be a scalar string.' );
                    $fileNode2 = (integer)$attr['target'];
                    unset( $attr['target'] );
                }
                if ( $fileNode1 == -1 || $fileNode2 == -1 )
                    throw new InvalidContentException(
                        'JSON edges must have "source" and "target" properties.' );

                // Map the file's node IDs into internal node IDs.
                if ( !isset( $nodeIDMap[$fileNode1] ) ||
                    !isset( $nodeIDMap[$fileNode2] ) )
                    throw new InvalidContentException(
                        'JSON edges source/target IDs do not match any nodes.' );
                $memoryNode1 = $nodeIDMap[$fileNode1];
                $memoryNode2 = $nodeIDMap[$fileNode2];

                // Add the edge.
                $graph->addEdge( $memoryNode1, $memoryNode2, $attr );
            }
        }

        $graph->setAttributes( $moreAttributes );

        return $graph;
    }

    /**
     * Decodes attributes for a graph, node, or edge, and returns an
     * associative array containing those attributes.
     *
     * @param array $content  the content.
     *
     * @return array  the associative array of decoded attributes.
     *
     * @throws InvalidContentException if the content cannot be parsed.
     */
    private function _decodeAttributes( &$content )
    {
        // Create attributes
        // -----------------------------------------------------
        // The format supports a "label", which we use as the
        // graph "name". We also parse optional "metadata" for
        // additional well-known and custom graph attributes.
        //
        // Good example:
        //  "label":  "my graph",
        //  "metadata": {
        //    "longName": "long name",
        //    "description": "description",
        //    "whatever": "something"
        //  }

        // Convert the object to an attributes array that initially
        // contains all properties. We'll type check and clean things
        // out below.
        $attributes = get_object_vars( $content );


        // Label
        // -----------------------------------------------------
        // If 'label' exists, make sure it is a string, then
        // rename it as 'name'.
        if ( isset( $attributes['label'] ) )
        {
            // Label's value must be a string.
            $value = $attributes['label'];
            if ( !is_scalar( $value ) )
                throw new InvalidContentException(
                    'JSON graph "label" property must be a scalar string.' );

            $attributes['name'] = (string)$value;
            unset( $attributes['label'] );
        }


        // Metadata
        // -----------------------------------------------------
        // If 'metadata' exists, pull it out and move its values
        // up into the attributes array.
        if ( isset( $attributes['metadata'] ) )
        {
            // Metadata's value must be an object.
            $value = $attributes['metadata'];
            if ( !is_object( $value ) )
                throw new InvalidContentException(
                    'JSON graph "metadata" property must be an object.' );

            $metaAttributes = (array)$value;
            unset( $attributes['metadata'] );
            $attributes = array_merge( $attributes, $metaAttributes );
        }

        // Make sure well-known attributes 'longName' and 'description'
        // have scalar string values, if they are provided.
        if ( isset( $attributes['longName'] ) )
        {
            if ( !is_scalar( $attributes['longName'] ) )
                throw new InvalidContentException(
                    'JSON graph "longName" property must be a scalar string.' );
        }
        if ( isset( $attributes['description'] ) )
        {
            if ( !is_scalar( $attributes['description'] ) )
                throw new InvalidContentException(
                    'JSON graph "description" property must be a scalar string.' );
        }

        return $attributes;
    }
    // @}





//----------------------------------------------------------------------
// Encode methods
//----------------------------------------------------------------------
    /**
     * @name Encode methods
     */
    // @{
    /**
     * @copydoc AbstractFormat::encode
     *
     * #### Encode limitations
     * The JSON format supports encoding one or more
     * SDSC\StructuredData\Graph objects to the format. When multiple graphs
     * are provided, encoding always uses ENCODE_AS_OBJECT_WITH_SCHEMA,
     * regardless of the value of the $options argument.
     */
    public function encode( &$objects, $options = 0 )
    {
        //
        // Validate arguments
        // -----------------------------------------------------
        // Check that we have an array and that all array entries
        // are Graph objects.
        if ( empty( $objects ) )
            return NULL;            // No graph to encode

        if ( !is_array( $objects ) )
            throw new \InvalidArgumentException(
                'JSON encode requires an array of objects.' );

        foreach ( $objects as &$object )
        {
            if ( !is_a( $object, 'SDSC\StructuredData\Graph', false ) )
                throw new \InvalidArgumentException(
                    'JSON encode objects must be graphs.' );
        }

        //
        // Encode
        // -----------------------------------------------------
        // When there is only one graph, use the $options argument
        // to decide to encode it as a single object, or as an
        // array of graphs (with just one) with the schema header.
        //
        // Otherwise, when there are multiple graphs, ignore $options
        // and always encode with the schema header since that's
        // the only syntax that supports multiple graphs.
        if ( count( $objects ) == 1 && $options == self::ENCODE_AS_OBJECT )
            return $this->_encodeGraph( $objects[0], '', '' );

        // Otherwise ENCODE_AS_OBJECT_WITH_SCHEMA (default)
        return $this->_encodeAsObjectWithSchema( $objects );
    }

    /**
     * Encodes the given array of graphs, starting with a header
     * that includes the graph's attributes, followed by a "graphs"
     * property that includes the graphs.
     *
     * @param  array    $graphs the array of graphs to be encoded.
     *
     * @return  string          the JSON text that encodes the graph.
     */
    private function _encodeAsObjectWithSchema( &$graphs )
    {
        // Sample output:
        //
        // {
        //   "type": "json-graph",
        //   "graphs": [ ... ]
        // }
        //
        // Open
        // -------------------------------------------------
        $text   = "{\n";
        $indent = '  ';
        $indent2 = '    ';

        // Header
        // -------------------------------------------------
        $text .= "$indent\"type\": \"json-graph\",\n";


        // Graphs
        // -------------------------------------------------
        $text .= "$indent\"graphs\": [\n";
        $n = count( $graphs );
        for ( $i = 0; $i < $n; ++$i )
        {
            if ( $i != $n - 1 )
                $text .= $this->_encodeGraph( $graphs[$i], $indent2, ',' );
            else
                $text .= $this->_encodeGraph( $graphs[$i], $indent2, '' );
        }
        $text .= "$indent]\n";


        // Close
        // -------------------------------------------------
        $text .= "}\n";
        return $text;
    }

    /**
     * Encodes the given graph, indenting each line with the given string,
     * and ending the last graph with the given "comma" string.
     *
     * @param  Graph   $graph   the graph object to be encoded.
     *
     * @param  string  $indent  the text string to prepend to every line
     * of encoded text.
     *
     * @param  string  $comma   a comma or empty string to add after the
     * graph.
     */
    private function _encodeGraph( &$graph, $indent, $comma )
    {
        // The incoming graph cannot be a NULL. But this should never
        // happen since the calling code has already checked for this.
        // But, we can be paranoid anyway.
        // @codeCoverageIgnoreStart
        if ( $graph == NULL )
            return;
        // @codeCoverageIgnoreEnd

        // Sample output:
        //
        // {
        //   "label": "my graph",
        //   "metadata": {
        //     "longName": "long name",
        //     "description": "description",
        //     "whatever": "something"
        //   }
        //   "nodes": [ ... ],
        //   "edges": [ ... ]
        // }
        //
        // Open
        // -------------------------------------------------
        $text = "$indent{\n";
        $indent2 = $indent . '  ';
        $indent3 = $indent . '    ';

        $nodeIDs = $graph->getAllNodes( );
        $nNodes = count( $nodeIDs );

        $edgeIDs = $graph->getAllEdges( );
        $nEdges = count( $edgeIDs );


        // Header
        // -------------------------------------------------
        $name = $graph->getName( );
        if ( !empty( $name ) )
            $text .= "$indent2\"label\": \"$name\",\n";
        $text .= $this->_encodeMetadata( $graph->getAttributes( ), $indent2, ',' );



        // Nodes
        // -------------------------------------------------
        $text .= "$indent2\"nodes\": [\n";
        for ( $i = 0; $i < $nNodes; ++$i )
        {
            if ( $i != $nNodes - 1 )
                $text .= $this->_encodeNode( $graph, $nodeIDs[$i], $indent3, ',' );
            else
                $text .= $this->_encodeNode( $graph, $nodeIDs[$i], $indent3, '' );
        }
        $text .= "$indent2],\n";


        // Edges
        // -------------------------------------------------
        $text .= "$indent2\"edges\": [\n";
        for ( $i = 0; $i < $nEdges; ++$i )
        {
            if ( $i != $nEdges - 1 )
                $text .= $this->_encodeEdge( $graph, $edgeIDs[$i], $indent3, ',' );
            else
                $text .= $this->_encodeEdge( $graph, $edgeIDs[$i], $indent3, '' );
        }
        $text .= "$indent2]\n";


        // Close
        // -------------------------------------------------
        $text .= "$indent}$comma\n";
        return $text;
    }

    /**
     * Encodes the given node, indenting each line with the given string,
     * and ending the last node with the given "comma" string.
     *
     * @param  Graph   $graph   the graph object to be encoded.
     *
     * @param  integer $nodeID  the ID of the node to be encoded.
     *
     * @param  string  $indent  the text string to prepend to every line
     * of encoded text.
     *
     * @param  string  $comma   a comma or empty string to add after the
     * graph.
     */
    private function _encodeNode( &$graph, $nodeID, $indent, $comma )
    {
        // Sample output:
        //
        // {
        //   "label": "my node",
        //   "id": "123",
        //   "metadata": {
        //     "longName": "long name",
        //     "description": "description",
        //     "whatever": "something"
        //   }
        // }
        //
        // Open
        // -------------------------------------------------
        $text = "$indent{\n";
        $indent2 = "$indent  ";


        // Content
        // -------------------------------------------------
        $attr = $graph->getNodeAttributes( $nodeID );
        $name = $graph->getNodeName( $nodeID );
        if ( !empty( $name ) )
            $text .= "$indent2\"label\": \"$name\",\n";

        if ( isset( $attr['name'] ) )
            unset( $attr['name'] );       // Name already handled

        if ( count( $attr ) != 0 )
        {
            $text .= "$indent2\"id\": \"$nodeID\",\n";
            $text .= $this->_encodeMetadata( $attr, $indent2, '' );
        }
        else
            $text .= "$indent2\"id\": \"$nodeID\"\n";


        // Close
        // -------------------------------------------------
        $text .= "$indent}$comma\n";
        return $text;
    }

    /**
     * Encodes the given edge, indenting each line with the given string,
     * and ending the last edge with the given "comma" string.
     *
     * @param  Graph   $graph   the graph object to be encoded.
     *
     * @param  integer $edgeID  the ID of the edge to be encoded.
     *
     * @param  string  $indent  the text string to prepend to every line
     * of encoded text.
     *
     * @param  string  $comma   a comma or empty string to add after the
     * graph.
     */
    private function _encodeEdge( &$graph, $edgeID, $indent, $comma )
    {
        // Sample output:
        //
        // {
        //   "label": "my edge",
        //   "source": "1",
        //   "target": "2",
        //   "metadata": {
        //     "longName": "long name",
        //     "description": "description",
        //     "whatever": "something"
        //   }
        // }
        //
        // Open
        // -------------------------------------------------
        $text = "$indent{\n";
        $indent2 = "$indent  ";


        // Content
        // -------------------------------------------------
        $attr = $graph->getEdgeAttributes( $edgeID );
        $name = $graph->getEdgeName( $edgeID );
        if ( !empty( $name ) )
            $text .= "$indent2\"label\": \"$name\",\n";

        if ( isset( $attr['name'] ) )
            unset( $attr['name'] );         // Name already handled

        $nodeIDs = $graph->getEdgeNodes( $edgeID );
        $node1 = $nodeIDs[0];
        $node2 = $nodeIDs[1];

        $text .= "$indent2\"source\": \"$node1\",\n";
        if ( count( $attr ) != 0 )
        {
            $text .= "$indent2\"target\": \"$node2\",\n";
            $text .= $this->_encodeMetadata( $attr, $indent2, '' );
        }
        else
            $text .= "$indent2\"target\": \"$node2\"\n";


        // Close
        // -------------------------------------------------
        $text .= "$indent}$comma\n";
        return $text;
    }

    /**
     * Encodes the given attributes array as metadata, indenting each
     * line with the given string, and ending the last syntax with the
     * given "comma" string.
     *
     * @param  array $attributes the attributes to be encoded.
     *
     * @param  string  $indent  the text string to prepend to every line
     * of encoded text.
     *
     * @param  string  $comma   a comma or empty string to add after the
     * graph.
     */
    private function _encodeMetadata( $attributes, $indent, $comma )
    {
        // Sample output:
        //
        // "metadata": {
        //   "longName": "long name",
        //   "description": "description",
        //   "whatever": "something"
        // }
        //


        $keys = array_keys( $attributes );
        $n = count( $keys );

        if ( $n == 0 )
            return '';                      // No attributes


        // Open
        // -------------------------------------------------
        $text    = "$indent\"metadata\": {\n";
        $indent2 = "$indent  ";


        // Content
        // -------------------------------------------------
        for ( $i = 0; $i < $n; ++$i )
        {
            $key = $keys[$i];
            $value = $attributes[$key];

            if ( is_int( $value ) || is_float( $value ) || is_bool( $value ) )
                $text .= "$indent2\"$key\": $value";

            else if ( is_null( $value ) )
                $text .= "$indent2\"$key\": null";

            else if ( is_string( $value ) )
                $text .= "$indent2\"$key\": \"$value\"";

            else if ( is_object( $value ) || is_array( $value ) )
            {
                // Don't know what this is, so encode it blind.
                $text .= "$indent2\"$key\": " . json_encode( $value );
            }

            if ( $i != $n - 1 )
                $text .= ",\n";
            else
                $text .= "\n";

        }

        // Close
        // -------------------------------------------------
        $text .= "$indent}$comma\n";
        return $text;
    }
    // @}
}

/**
 * @file
 * Defines SDSC\StructuredData\Format\JSONTableFormat to parse and
 * serialize data in the JSON (JavaScript Object Notation) text syntax
 * for tables.
 */

namespace SDSC\StructuredData\Format;


use SDSC\StructuredData\Table;





/**
 * @class JSONTableFormat
 * JSONTableFormat provides decode and encode functions that map
 * between JSON (JavaScript Object Notation) text and a
 * \SDSC\StructuredData\Table.
 *
 * JSON is a general-purpose syntax for describing objects, arrays,
 * scalars, and arrays of objects, arrays, of scalars to an arbitrary
 * nesting depth. This class, however, focuses on a narrower subset of
 * JSON usage in order to build tables.
 *
 *
 * #### Table syntax
 * A JSON table is a list of rows where each row has the same number of
 * columns of values. Each column always has a short name, and in some
 * syntax forms each column also has a long name, description, and data type.
 * In some syntax forms, the table itself may have a short name, long
 * name, and description.
 *
 * There are several syntax variants:
 * - Array of arrays
 * - Array of objects
 * - Object containing metadata, schema, and array of arrays
 * - Object containing metadata, schema, and array of objects
 *
 * ##### Array of arrays
 * JSON tables can be expressed as an array of arrays of scalars (see
 * ENCODE_AS_ARRAY_OF_ARRAYS). Each array gives one row of data. The
 * first array gives column names. All arrays must have the same number
 * of columns.
 * <pre>
 *  [
 *      [ "Column 1", "Column 2", "Column 3" ],
 *      [ 1, 2, 3 ],
 *      [ 4, 5, 6 ]
 *  ]
 * </pre>
 *
 * ##### Array of objects
 * JSON tables can be expressed as an array of objects (see
 * ENCODE_AS_ARRAY_OF_OBJECTS).  Each object gives one row of data.
 * Property names for the first object give column names, and the same
 * properties must be used for all further row objects. All objects
 * must have these same properties, though they may be given in any
 * order.
 * <pre>
 *  [
 *      { "Column 1": 1, "Column 2": 2, "Column 3": 3 },
 *      { "Column 1": 4, "Column 2": 5, "Column 3": 6 }
 *  ]
 * </pre>
 *
 * ##### Parent object
 * JSON tables can be included in a parent object within a "table"
 * property (see ENCODE_AS_OBJECT):
 * <pre>
 *  {
 *      "table": [ ... ]
 *  }
 * </pre>
 *
 * ##### Table names
 * JSON tables within a parent object may have additional properties
 * that give the table's short name (name), long name (title), and
 * description.  The name, title, and description property values may
 * be a scalar string or an array with at least one scalar string value.
 * Non-string values are silently converted to strings.
 * <pre>
 *  {
 *      "name":  [ "tbl" ],
 *      "title": [ "Big table" ],
 *      "description": [ "A big table with lots of data" ],
 *      "table": [ ... ]
 *  }
 * </pre>
 *
 * ##### Table schema name
 * JSON tables can have a microformat schema name that refers to
 * a well-known schema by setting the "type" property of the parent
 * object.  The type property value may be an array or a scalar with a
 * single string value.
 * <pre>
 *  {
 *      "type": [ "json-array" ],
 *      "table": [ ... ]
 *  }
 * </pre>
 *
 * Several generic schema names refer to the above tables containing
 * column names.  These are functionally identical to having no schema name:
 *  @li "json-array"
 *  @li "json-table"
 *  @li "array"
 *  @li "table"
 *
 * Other well-known schema names may have more complex schemas associated
 * with them that define the number and names of columns, and column
 * data types. For instance, the "messages" type is used for a 2-column
 * table where each row has a text message and a time stamp.
 *
 * When a well-known schema name maps to a schema with defined columns,
 * the first row of the table is not used for column names.
 *
 * ##### Table schema
 * JSON tables can include an explicit schema to provide column short names,
 * long names, descriptions, and data types using the JSON Table Schema
 * microformat (see ENCODE_AS_OBJECT_WITH_SCHEMA).  When an explicit schema
 * is given, the first row of the table is not used for column names.
 * <pre>
 *  {
 *      "type": [ "json-array" ],
 *      "fields" : [
 *          {
 *              "name": "col1",
 *              "title": "Column 1",
 *              "type": "number",
 *              "format": "default",
 *              "description": "This is column 1"
 *          },
 *          {
 *              "name": "col2",
 *              "title": "Column 2",
 *              "type": "number",
 *              "format": "default",
 *              "description": "This is column 2"
 *          }
 *      ],
 *      "table": [ ... ]
 *  }
 * </pre>
 *
 *
 * #### Table decode limitations
 * The amount of table and column descriptive information available
 * in a JSON file depends upon which syntax form above is used. For
 * instance, in some forms, columns only have short names. In other
 * forms columns have short and long names, descriptions, and data
 * types. Similarly, in some forms tables have names and descriptions,
 * while in other forms they do not. In all of these cases, names
 * and descriptions default to empty strings and are only set if the
 * syntax parsed includes names and descriptions.
 *
 * When a schema is provided, columns may have specific data types.
 * But when column data types are not chosen, these data types
 * are automatically inferred by the SDSC\StructuredData\Table class.
 * That class scans through each column and looks for a consistent
 * interpretation of the values as integers, floating-point numbers,
 * booleans, etc., then sets the data type accordingly.
 *
 *
 * #### Table encode limitations
 * The encoder can output tables in several JSON syntax forms. Some
 * of those forms include full information on the table's short and
 * long names and description, and on the short and long names,
 * description, and data types for all table columns.  But other
 * syntax forms omit most of this information and only output column
 * short names as the first row of the table.
 *
 * Column value data types are used to guide JSON encoding. Values
 * that are integers, floating-point numbers, booleans, or nulls are
 * output as single un-quoted tokens. All other value types are output
 * as single-quoted strings.
 *
 *
 * @see     SDSC\StructuredData\Table   the StructuredData Table class
 *
 * @author  David R. Nadeau / University of California, San Diego
 *
 * @date    1/27/2016
 *
 * @since   0.0.1
 *
 * @version 0.0.1  Initial development.
 *
 * @version 0.0.2  Revised to provide format attributes per RDA, and to
 * create tables using the updated Table API that uses an array of attributes.
 *
 * @version 0.0.3  Moved Table, Tree, and Graph handling into separate classes.
 */
final class JSONTableFormat
    extends AbstractFormat
{
//----------------------------------------------------------------------
// Constants
//----------------------------------------------------------------------
    /**
     * An encoding style that generates a JSON array of row arrays.
     * The first row array contains the column names.
     *
     * <pre>
     * [
     *   [ "Column1", "Column2", "Column3", ... ],
     *   [ value1, value2, value3, ... ],
     *   [ value1, value2, value3, ... ],
     *   ...
     * ]
     * </pre>
     */
    const ENCODE_AS_ARRAY_OF_ARRAYS = 1;

    /**
     * An encoding style that generates a JSON array of row objects.
     * Each object has the same properties based upon the column names.
     *
     * <pre>
     * [
     *   { "Column1": value1, "Column2": value2, "Column3": value3, ... },
     *   { "Column1": value1, "Column2": value2, "Column3": value3, ... },
     *   ...
     * ]
     * </pre>
     */
    const ENCODE_AS_ARRAY_OF_OBJECTS = 2;

    /**
     * An encoding style identical to ENCODE_AS_ARRAY_OF_OBJECTS, but
     * with a parent object that provides the table's name, description,
     * and schema type, if any. The table is contained within the
     * 'table' property of the object.
     *
     * <pre>
     * {
     *   "name": "table short name",
     *   "title": "table long name"
     *   "description": "table description",
     *   "type": "table source schema name",
     *   "table": [ ... ]
     * }
     * </pre>
     */
    const ENCODE_AS_OBJECT = 3;

    /**
     * An encoding style identical to ENCODE_AS_OBJECT, but with
     * a schema included that provides column names, descriptions,
     * and data types.
     *
     * This is the default encoding.
     *
     * <pre>
     * {
     *   "name": "table short name",
     *   "title": "table long name"
     *   "description": "table description",
     *   "type": "table source schema name",
     *   "fields": [
     *     {
     *       "name": "Column short name",
     *       "title": "Column long name",
     *       "description": "Column description",
     *       "type": "Column data type",
     *       "format": "Column data type format"
     *     },
     *     { ... }
     *   ]
     *   "table": [ ... ]
     * }
     * </pre>
     */
    const ENCODE_AS_OBJECT_WITH_SCHEMA = 4;



    /**
     * A list of well-known table schemas.
     */
    public static $WELL_KNOWN_TABLE_SCHEMAS = array(
        'messages' => array(
            array(
                'name'        => 'message',
                'title'       => 'Message',
                'type'        => 'string',
                'format'      => 'default',
                'description' => 'The ticker message'
            ),
            array(
                'name'        => 'time',
                'title'       => 'Time',
                'type'        => 'string',
                'format'      => 'default',
                'description' => 'The ticker timestamp'
            )
        )
    );


    /**
     * A hash table of accepted column data types.
     */
    private static $WELL_KNOWN_COLUMN_TYPES = array(
        'any' => 1,     'boolean' => 1, 'date' => 1,    'datetime' => 1,
        'integer' => 1, 'null' => 1,    'number' => 1,  'string' => 1,
        'time' => 1
    );



//----------------------------------------------------------------------
// Constructors & Destructors
//----------------------------------------------------------------------
    /**
     * @name Constructors
     */
    // @{
    /**
     * Constructs and returns a new format object that may be used to
     * decode and encode tables in JSON (JavaScript Object Notation).
     */
    public function __construct( )
    {
        parent::__construct( );

        $this->attributes['syntax']         = 'JSON';
        $this->attributes['name']           = 'json-table';
        $this->attributes['longName']       = 'JavaScript Object Notation (JSON) Table';
        $this->attributes['MIMEType']       = 'application/json';
        $this->attributes['fileExtensions'] = array( 'json' );
        $this->attributes['description'] =
            'The JSON (JavaScript Object Notation) format encodes ' .
            'a variety of data, including tables. ' .
            'Tabular data may have an unlimited number of rows and ' .
            'columns with an optional schema. Each column may have ' .
            'a short name, long name, and description. All rows have ' .
            'a value for every column. Row values are typically integers ' .
            'or floating-point numbers, but they also may be strings and ' .
            'booleans.';
        $this->attributes['expectedUses'] = array(
            'Tabular data with named columns and rows of values'
        );
        $this->attributes['standards'] = array(
            array(
                'issuer' => 'RFC',
                'name' => 'IETF RFC 7159',
                'natureOfApplicability' => 'specifies',
                'details' => 'The JavaScript Object Notation (JSON) Data Interchange Format'
            ),
            array(
                'issuer' => 'ad hoc',
                'name' => 'JSON Table',
                'natureOfApplicability' => 'specifies',
                'details' => 'http://dataprotocols.org/json-table-schema/'
            )
        );

        // Unknown:
        //  identifier
        //  creationDate
        //  lastModificationDate
        //  provenance
    }
    // @}

    /**
     * @name Destructors
     */
    // @{
    /**
     * Destroys a previously-constructed format object.
     */
    public function __destruct( )
    {
        parent::__destruct( );
    }
    // @}





//----------------------------------------------------------------------
// Encode/decode attribute methods
//----------------------------------------------------------------------
    /**
     * @name Encode/decode attribute methods
     */
    // @{
    /**
     * @copydoc AbstractFormat::getComplexity
     */
    public function getComplexity( )
    {
        return 10;
    }

    /**
     * @copydoc AbstractFormat::canDecodeTables
     */
    public function canDecodeTables( )
    {
        return true;
    }

    /**
     * @copydoc AbstractFormat::canEncodeTables
     */
    public function canEncodeTables( )
    {
        return true;
    }
    // @}





//----------------------------------------------------------------------
// Encode/decode methods
//----------------------------------------------------------------------
    /**
     * @name Encode/decode methods
     */
    // @{
    /**
     * @copydoc AbstractFormat::decode
     *
     * #### Decode limitations
     * The JSON format always returns an array containing a single
     * SDSC\StructuredData\Table object.
     */
    public function decode( &$text )
    {
        // Parse JSON
        // -----------------------------------------------------
        //   Parse JSON text.
        if ( empty( $text ) )
            return array( );        // No table

        // Passing 'false' to json_decode( ) means that it should *not*
        // silently convert objects into arrays. We need to know whether
        // something in the text is an object or array because they have
        // different meanings and different parse paths below.
        $content = json_decode( $text, false );

        if ( $content == NULL )
        {
            // Failure to parse.
            $code = json_last_error( );
            switch ( $code )
            {
            case JSON_ERROR_STATE_MISMATCH:
            case JSON_ERROR_SYNTAX:
                throw new SyntaxException(
                    'Malformed JSON. Problem with commas, brackets, or parenthesis?' );
            case JSON_ERROR_CTRL_CHAR:
            case JSON_ERROR_UTF8:
                throw new SyntaxException(
                    'Malformed JSON. Control characters or bad UTF-8?' );

            // The maximum nesting depth is not defined by PHP and may
            // vary with changes in the implementation. This makes unit
            // testing for this case is not practical, so we ignore it.
            // @codeCoverageIgnoreStart
            case JSON_ERROR_DEPTH:
                throw new SyntaxException(
                    'Malformed JSON. Nesting too deep.' );
            // @codeCoverageIgnoreEnd
            }

            // There is no content, and yet we don't know what the
            // error is, if any.
            throw new SyntaxException(
                'Malformed JSON.' );
        }
        // At this point we don't know what type of content we have.
        // We could have a table in any of several formats.


        // Determine content type
        // -----------------------------------------------------
        //   If the content is an array, we have a table.
        //
        //   If the content is an object, look for a few
        //   tell-tale properties to see what we have.
        if ( is_array( $content ) )
            return $this->_decodeTableArray( $content, NULL );

        if ( is_object( $content ) )
            return $this->_decodeTableObject( $content );

        // Otherwise we don't know what it is.
        throw new SyntaxException(
            'Unrecognized JSON content. Does not appear to be a table.' );
    }





    /**
     * @copydoc AbstractFormat::encode
     *
     * #### Encode limitations
     * The JSON format only supports encoding a single
     * SDSC\StructuredData\Table in the format. An exception is thrown
     * if the $objects argument is not an array, is empty, contains
     * more than one object, or it is not a Table.
     */
    public function encode( &$objects, $options = 0 )
    {
        //
        // Validate arguments
        // -----------------------------------------------------
        if ( $objects == NULL )
            return NULL;            // No table to encode

        if ( !is_array( $objects ) )
            throw new \InvalidArgumentException(
                'JSON encode requires an array of objects.' );

        if ( count( $objects ) > 1 )
            throw new \InvalidArgumentException(
                'JSON encode only supports encoding a single object.' );
        $object = &$objects[0];

        //
        // Encode
        // -----------------------------------------------------
        // Reject anything that isn't a Table.
        if ( is_a( $object, 'SDSC\StructuredData\Table', false ) )
            return $this->_encodeTable( $object, $options );
        else
            throw new \InvalidArgumentException(
                'JSON encode object must be a table.' );
    }
    // @}






//----------------------------------------------------------------------
// Table methods
//----------------------------------------------------------------------
    /**
     * @name Table methods
     */
    // @{
    /**
     * Decodes a table from an array.
     *
     * This is the simplest form of JSON table. It has no table name,
     * schema name, or schema. The array has the table's rows. Each
     * row may be an array or an object. The first row sets the column
     * names.
     *
     * Array of arrays:
     * <pre>
     *  [
     *      [ "Column 1", "Column 2", "Column 3" ],
     *      [ 1, 2, 3 ],
     *      [ 4, 5, 6 ]
     *  ]
     * </pre>
     *
     * Array of objects:
     * <pre>
     *  [
     *      { "Column 1": 1, "Column 2": 2, "Column 3": 3 },
     *      { "Column 1": 4, "Column 2": 5, "Column 3": 6 }
     *  ]
     * </pre>
     *
     *
     * @param   array  $content  an array containing the freshly
     * parsed array of table data.
     *
     * @param   array  $columns  an array of column attributes, if any.
     *
     * @return  Table              the table parsed from the content,
     * including columns and rows.
     *
     * @throws  InvalidContentException  if the table is malformed.
     */
    private function _decodeTableArray( &$content, $columns = NULL )
    {
        if ( count( $content ) == 0 )
            return array( );            // No table

        if ( $columns == NULL )
            $columns = array( );

        $rows = NULL;


        // Get rows and columns
        // -----------------------------------------------------
        //   Rows may be arrays or objects. Handle them a bit
        //   differently.
        if ( is_array( $content[0] ) )
        {
            // Each row is an array.
            //
            // If the 1st row array has no columns, we have a table
            // with no columns. Since a table cannot have rows without
            // columns, giving a row without columns is an error.
            //
            // Error example:
            //  [
            //      [ ]
            //  ]
            if ( count( $content[0] ) == 0 )
                throw new InvalidContentException(
                    'JSON table first row should have column names, but is empty.' );

            // We have a 1st row with columns. Use it to get
            // column names, if needed.  Each column name must be a scalar,
            // which we convert to a string, if needed.
            //
            // Good example:
            //  [
            //      [ "Column 1", "Column 2", "Column 3" ]
            //  ]
            if ( count( $columns ) == 0 )
            {
                $header = array_shift( $content );
                foreach ( $header as &$name )
                {
                    if ( !is_scalar( $name ) )
                        throw new InvalidContentException(
                            'JSON table first row column names must be scalars (usually strings).' );
                    if ( $name === '' )
                        throw new InvalidContentException(
                            'JSON table column name must not be empty.' );
                    $columns[] = array( 'name' => (string)$name );
                }
            }

            // There may be further rows.  All of them must be
            // arrays too.  All values in each row must be scalars.
            // Check to be sure.
            //
            // Error example:
            //  [
            //      [ "Column 1", "Column 2" ],
            //      { "this": "that", "thing": 42 }
            //  ]
            //
            // Good example:
            //  [
            //      [ "Column 1", "Column 2" ],
            //      [ 123, 456 ],
            //      [ 789, 012 ]
            //  ]
            $rows = &$content;
            foreach ( $rows as &$row )
            {
                if ( !is_array( $row ) )
                    throw new InvalidContentException(
                        'JSON table rows should be all arrays or all objects.' );
                foreach ( $row as &$value )
                {
                    if ( !is_scalar( $value ) && $value != NULL )
                        throw new InvalidContentException(
                            'JSON table row values must be scalars.' );
                }
            }
        }
        else if ( is_object( $content[0] ) )
        {
            // Each row is an object.
            //
            // If the 1st row object has no properties, we have
            // a table with no columns. Since a table cannot have
            // rows without columns, giving a row without columns
            // is an error.
            //
            // Error example:
            //  [
            //      { }
            //  ]
            //
            // If we have a 1st row with columns, use it to get
            // column names, if needed.  Each column name must be a scalar,
            // which we convert to a string, if needed.
            //
            // Good example:
            //  [
            //      { "Column 1": 123, "Column 2": 456, "Column 3": 789 }
            //  ]
            if ( count( $columns ) == 0 )
            {
                $properties = get_object_vars( $content[0] );
                if ( count( $properties ) == 0 )
                    throw new InvalidContentException(
                        'JSON table first row should have column names, but is empty.' );
                foreach ( $properties as $name => &$value )
                {
                    if ( $name == '_empty_' )
                        throw new InvalidContentException(
                            'JSON table column name must not be empty.' );
                    $columns[] = array( 'name' => (string)$name );
                }
            }

            // There may be further rows.  All of them must be
            // objects too.  All values in each row must be scalars.
            // Check to be sure.
            //
            // Error example:
            //  [
            //      { "Column 1": 123, "Column 2": 456, "Column 3": 789 },
            //      [ 1, 2, 3 ]
            //  ]
            //
            // Good example:
            //  [
            //      { "Column 1": 123, "Column 2": 456, "Column 3": 789 },
            //      { "Column 1": 321, "Column 2": 654, "Column 3": 987 }
            //  ]
            //
            // Convert all objects into arrays of values for further
            // processing.
            $rows = array( );
            foreach ( $content as &$rowObject )
            {
                if ( !is_object( $rowObject ) )
                    throw new InvalidContentException(
                        'JSON table rows should be all arrays or all objects' );

                $row = array( );
                foreach ( $columns as &$column )
                {
                    $name = $column['name'];
                    if ( !property_exists( $rowObject, $name ) )
                        throw new InvalidContentException(
                            'JSON table row objects must all have the same properties as the 1st row.' );
                    $value = $rowObject->{$name};
                    if ( !is_scalar( $value ) && $value != NULL )
                        throw new InvalidContentException(
                            'JSON table row values must be scalars.' );
                    $row[] = $value;
                }
                $rows[] = $row;
            }
        }
        else
            throw new SyntaxException(
                'Unrecognized JSON content. Does not appear to be a table.' );

        // Make sure all of the rows are complete.
        $nColumns = count( $columns );
        foreach ( $rows as &$row )
        {
            if ( count( $row ) != $nColumns )
                throw new InvalidContentException(
                    'JSON table rows must all have one value per column.' );
        }


        // Build table
        // -----------------------------------------------------
        //  No table name or description.
        //  JSON as syntax. No schema.
        $attributes = array(
            // 'name' unknown
            // 'longName' unknown
            // 'description' unknown
            // 'sourceFileName' unknown
            'sourceMIMEType'   => $this->getMIMEType( ),
            'sourceSyntax'     => $this->getSyntax( ),
            'sourceSchemaName' => 'json-table'
        );
        $table = new Table( $attributes );

        foreach ( $columns as &$column )
            $table->appendColumn( $column );

        if ( count( $rows ) != 0 )
            $table->appendRows( $rows );

        return array( $table );
    }




    /**
     * Decodes a table from an object.
     *
     * The object has already been recognized as having a 'table'
     * property containing the table's rows. Additional properties
     * may be present for the table's name, etc.
     *
     * Minimal object:
     * <pre>
     *  {
     *      "table": [ ... ]
     *  }
     * </pre>
     *
     * Object with schema name:
     * <pre>
     *  {
     *      "type": [ "json-array" ],
     *      "table": [ ... ]
     *  }
     * </pre>
     *
     * Object with schema name and table attributes:
     * <pre>
     *  {
     *      "type":  [ "json-array" ],
     *      "name":  [ "tbl" ],
     *      "title": [ "Big table" ],
     *      "description": [ "A big table with lots of data" ],
     *      "table": [ ... ]
     *  }
     * </pre>
     *
     * Object with schema name, table attributes, and column attributes
     * (called 'fields' in the JSON Table Schema):
     * <pre>
     *  {
     *      "type":  [ "json-array" ],
     *      "name":  [ "tbl" ],
     *      "title": [ "Big table" ],
     *      "description": [ "A big table with lots of data" ],
     *      "fields" : [
     *          {
     *              "name": "col1",
     *              "title": "Column 1",
     *              "type": "number",
     *              "format": "default",
     *              "description": "This is column 1"
     *          },
     *          {
     *              "name": "col2",
     *              "title": "Column 2",
     *              "type": "number",
     *              "format": "default",
     *              "description": "This is column 2"
     *          }
     *      ],
     *      "table": [ ... ]
     *  }
     * </pre>
     *
     * @param  mixed  $content  an object containing the freshly
     * parsed properties and rows array of table data.
     *
     * @return  Table              the table parsed from the content,
     * including columns and rows.
     *
     * @throws  SyntaxException  if the content does not appear to be a table.
     *
     * @throws  InvalidContentException  if the table is malformed.
     */
    private function _decodeTableObject( &$content )
    {
        // Validate it is a table
        // -----------------------------------------------------
        // The 'table' property must exist and contain an array
        // of rows.
        //
        // Error example:
        //  {
        //      "table": 123
        //  }
        //
        // Good example:
        //  {
        //      "table": [
        //        [ 1, 2, 3 ]
        //      ]
        //  }
        if ( !property_exists( $content, 'table' ) )
            throw new SyntaxException(
                'Unrecognized JSON content. Does not appear to be a table.' );
        $rows = $content->table;
        if ( !is_array( $rows ) )
            throw new InvalidContentException(
                'JSON "table" property must be an array of rows.' );


        // Set default attributes.
        $attributes = array(
            // 'name' unknown
            // 'longName' unknown
            // 'description' unknown
            // 'sourceFileName' unknown
            // 'sourceMIMEType' unknown
            'sourceMIMEType'   => $this->getMIMEType( ),
            'sourceSyntax'     => $this->getSyntax( ),
            'sourceSchemaName' => 'json-table'
        );


        // Get table information
        // -----------------------------------------------------
        // Look for descriptive properties. These are all
        // optional.  All of them should be scalars or arrays.
        // If they are arrays, use the 1st value.
        //
        // Good example:
        //  {
        //      "type":  [ "json-array" ],
        //      "name":  [ "tbl" ],
        //      "title": [ "Big table" ],
        //      "description": [ "A big table with lots of data" ],
        //      "table": [ ... ]
        //  }
        $properties = get_object_vars( $content );
        if ( property_exists( $content, 'name' ) )
        {
            if ( is_scalar( $content->name ) )
                $name = (string)$content->name;
            else if ( is_array( $content->name ) &&
                count( $content->name ) > 0 )
                $name = (string)$content->name[0];
            else
                throw new InvalidContentException(
                    'JSON table "name" property must be a scalar or array (usually a string).' );
            $attributes['name'] = $name;
            unset( $properties['name'] );
        }

        if ( property_exists( $content, 'title' ) )
        {
            if ( is_scalar( $content->title ) )
                $title = (string)$content->title;
            else if ( is_array( $content->title ) &&
                count( $content->title ) > 0 )
                $title = (string)$content->title[0];
            else
                throw new InvalidContentException(
                    'JSON table "title" property must be a scalar or array (usually a string).' );
            $attributes['longName'] = $title;
            unset( $properties['longName'] );
        }

        if ( property_exists( $content, 'description' ) )
        {
            if ( is_scalar( $content->description ) )
                $description = (string)$content->description;
            else if ( is_array( $content->description ) &&
                count( $content->description ) > 0 )
                $description = (string)$content->description[0];
            else
                throw new InvalidContentException(
                    'JSON table "description" property must be a scalar or array (usually a string).' );
            $attributes['description'] = $description;
            unset( $properties['description'] );
        }


        // Add any further properties as-is to the table's attributes,
        // but skip the "fields" and "type" properties addressed below.
        unset( $properties['type'] );
        unset( $properties['fields'] );
        $attributes = array_merge( $attributes, $properties );


        // Get schema information
        // -----------------------------------------------------
        // Look for schema properties. These are all optional.
        //
        $schemaName = NULL;
        $schema     = NULL;

        // Get column info, if any.
        if ( property_exists( $content, 'fields' ) )
        {
            if ( !is_array( $content->fields ) )
                throw new InvalidContentException(
                    'JSON table schema "fields" must be an array.' );
            $schema = $content->fields;
        }

        // Get schema name (type), if any.  If we have the name,
        // but not the column info, then see if the name is well-known
        // and we already have the column info.
        if ( property_exists( $content, 'type' ) )
        {
            // The schema name must be a scalar string, or an array
            // with at least one scalar string.
            //
            // Good example:
            //  {
            //      "type":  [ "json-array" ],
            //      ...
            //  }
            if ( is_scalar( $content->type ) )
                $schemaName = (string)$content->type;
            else if ( is_array( $content->type ) &&
                count( $content->type ) > 0 )
                $schemaName = (string)$content->type[0];
            else
                throw new InvalidContentException(
                    'JSON table schema "type" must be a scalar or array (usually a string).' );


            // If we don't have the column info given explicitly,
            // then see if this is a well-known schema name for
            // which we already have the column info.
            //
            // Ignore generic schema names since they don't tell us
            // anything about what the columns should be.
            if ( !isset( $schema ) &&
                $schemaName != 'array' &&
                $schemaName != 'table' &&
                $schemaName != 'json-array' &&
                $schemaName != 'json-table' )
            {
                // Check the well-known schema table.
                //
                // Good example:
                //  {
                //      "type":  [ "messages" ],
                //      ...
                //  }
                if ( isset( self::$WELL_KNOWN_TABLE_SCHEMAS[$schemaName] ) )
                    $schema = self::$WELL_KNOWN_TABLE_SCHEMAS[$schemaName];
                else
                    throw new InvalidContentException(
                        'JSON table must define "fields" with column attributes, or have a well-known schema type.' );
                $attributes['sourceSchemaName'] = $schemaName;
            }
        }


        // Parse columns schema
        // -----------------------------------------------------
        $columns = array( );
        if ( isset( $schema ) )
        {
            // Use the schema for the column names.
            $columns = $this->_decodeTableSchema( $schema );
            if ( count( $columns ) == 0 )
                throw new InvalidContentException(
                    'JSON table schema should define columns, but is empty.' );
        }


        // Get rows and columns
        // -----------------------------------------------------
        //   The table part of the object is an array of rows.
        //   Parse it into a table, passing in the column info
        //   we've already gathered, if any.
        //
        //   This may throw an exception.
        //
        //   On success, we get an array. If the array is empty,
        //   the rows part of the table was empty. Create an
        //   empty table.
        //
        //   If the array is not empty, the rows part of the
        //   table was parsed into a Table object with all the
        //   rows and columns set. Update it with table attributes.
        $results = $this->_decodeTableArray( $rows, $columns );
        if ( count( $results ) == 0 )
        {
            // Rows were empty.  Create an empty table with the
            // given attributes and columns, but now rows.
            $table = new Table( $attributes );
            foreach ( $columns as &$c )
                $table->appendColumn( $c, 0 );
        }
        else
        {
            $table = $results[0];
            $table->setAttributes( $attributes );
        }
        return array( $table );
    }


    /**
     * Decodes a table schema and returns the array of column attributes.
     *
     * <pre>
     *      [
     *          {
     *              "name": "col1",
     *              "title": "Column 1",
     *              "type": "number",
     *              "format": "default",
     *              "description": "This is column 1"
     *          },
     *          {
     *              "name": "col2",
     *              "title": "Column 2",
     *              "type": "number",
     *              "format": "default",
     *              "description": "This is column 2"
     *          }
     *      ]
     * </pre>
     *
     * @param  array  $schema  an array of schema objects describing
     * columns in a table.
     *
     * @return array  an array of parsed column information for the
     * table.
     *
     * @throws  InvalidContentException  if the schema entries are not
     * arrays or objects.
     *
     * @throws  InvalidContentException  if the name, title, description
     * or type are not scalars.
     *
     * @throws  InvalidContentException  if the name is empty or missing.
     *
     * @throws  InvalidContentException  if the data type is not recognized.
     */
    private function _decodeTableSchema( $schema )
    {
        $columns = array( );
        $columnIndex = 0;

        // The schema is an array of objects, with one per column.
        foreach ( $schema as &$schemaColumn )
        {
            // Normally, each entry is an object. We'll accept an
            // associative array too.  Convert to an associative
            // array.
            $columnAttributes = array( );
            $columnArray      = NULL;

            if ( is_object( $schemaColumn ) )
                $columnArray = (array)$schemaColumn;
            else if ( is_array( $schemaColumn ) )
                $columnArray = $schemaColumn;
            else
                throw new InvalidContentException(
                    'JSON table schema "fields" items must be objects or arrays.' );


            // Get well-known column attributes:
            //  - name
            //  - title
            //  - description
            //  - type
            // Each of these must be either a scalar or an array with
            // at least one entry. The first entry in the array is used.
            // Values are cast to strings.
            //
            // Names cannot be empty strings, but the others can be.
            //
            // If the column name is not given, the column's numeric
            // index is used.
            if ( !isset( $columnArray['name'] ) )
                $columnAttributes['name'] = (string)$columnIndex;
            else
            {
                // Good example:
                //   "name": "Column 1"
                // or
                //   "name": [ "Column 1" ]
                //
                // Error example:
                //   "name": { "this": "that" }
                // or
                //   "name": ""
                if ( is_scalar( $columnArray['name'] ) )
                    $columnAttributes['name'] = (string)$columnArray['name'];
                else if ( is_array( $columnArray['name'] ) &&
                    count( $columnArray['name'] ) > 0 )
                    $columnAttributes['name'] = (string)$columnArray['name'][0];
                else
                    throw new InvalidContentException(
                        'JSON table schema column names must be scalars (usually strings).' );

                if ( $columnAttributes['name'] === '' )
                    throw new InvalidContentException(
                        'JSON table schema column names must not be empty.' );
                unset( $columnArray['name'] );
            }

            if ( isset( $columnArray['title'] ) )
            {
                // Good example:
                //   "title": "Column 1"
                // or
                //   "title": [ "Column 1" ]
                // or
                //   "title": ""
                //
                // Error example:
                //   "title": { "this": "that" }
                if ( is_scalar( $columnArray['title'] ) )
                    $columnAttributes['longName'] = (string)$columnArray['title'];
                else if ( is_array( $columnArray['title'] ) &&
                    count( $columnArray['title'] ) > 0 )
                    $columnAttributes['longName'] = (string)$columnArray['title'][0];
                else
                    throw new InvalidContentException(
                        'JSON table schema column titles must be scalars (usually strings).' );
                unset( $columnArray['title'] );
            }

            if ( isset( $columnArray['description'] ) )
            {
                // Good example:
                //   "description": "Column 1"
                // or
                //   "description": [ "Column 1" ]
                // or
                //   "description": ""
                //
                // Error example:
                //   "description": { "this": "that" }
                if ( is_scalar( $columnArray['description'] ) )
                    $columnAttributes['description'] = (string)$columnArray['description'];
                else if ( is_array( $columnArray['description'] ) &&
                    count( $columnArray['description'] ) > 0 )
                    $columnAttributes['description'] = (string)$columnArray['description'][0];
                else
                    throw new InvalidContentException(
                        'JSON table schema column descriptions must be scalars (usually strings).' );

                unset( $columnArray['description'] );
            }

            if ( isset( $columnArray['type'] ) )
            {
                // Good example:
                //   "type": "integer"
                // or
                //   "type": [ "integer" ]
                // or
                //   "type": ""
                //
                // Error example:
                //   "type": { "this": "that" }
                if ( is_scalar( $columnArray['type'] ) )
                    $columnAttributes['type'] = (string)$columnArray['type'];
                else if ( is_array( $columnArray['type'] ) &&
                    count( $columnArray['type'] ) > 0 )
                    $columnAttributes['type'] = (string)$columnArray['type'][0];
                else
                    throw new InvalidContentException(
                        'JSON table schema column types must be scalars (usually strings).' );

                // Verify the type is known.
                if ( !isset( self::$WELL_KNOWN_COLUMN_TYPES[$columnAttributes['type']] ) )
                    throw new InvalidContentException(
                        'JSON table schema type not recognized.' );

                unset( $columnArray['type'] );
            }
            $columns[] = $columnAttributes;
            ++$columnIndex;
        }
        return $columns;
    }





    /**
     * Encodes a table as JSON text, controlled by the given
     * options.
     *
     * @param  mixed   $table  a table object to be encoded.
     *
     * @param integer  $options  encoding options to control how
     * JSON text is generated.
     *
     * @return  string        the JSON text that encodes the table,
     * or a NULL if there was no table.
     */
    private function _encodeTable( &$table, $options )
    {
        if ( $table->getNumberOfColumns( ) <= 0 )
            return NULL;            // No data to encode


        // Encode header and rows
        // -----------------------------------------------------
        //   Encode the table either as an array, or as an object
        //   that includes the array as a property value.  Add
        //   the schema if needed.
        if ( $options == self::ENCODE_AS_ARRAY_OF_ARRAYS )
        {
            $text  = "[\n";
            $text .= $this->_encodeTableColumnsAsArray( '  ', $table );
            $text .= $this->_encodeTableRowsAsArrays( '  ', $table );
            $text .= "]\n";
            return $text;
        }
        if ( $options == self::ENCODE_AS_ARRAY_OF_OBJECTS )
        {
            $tmp = $this->_encodeTableRowsAsObjects( '  ', $table );
            if ( $tmp === '' )
                return '';
            return "[\n$tmp ]\n";
        }
        if ( $options == self::ENCODE_AS_OBJECT )
        {
            $text  = "{\n";
            $text .= $this->_encodeTableObjectHeader( '  ', $table );
            $text .= "  \"table\": [\n";
            $text .= $this->_encodeTableRowsAsObjects( '    ', $table );
            $text .= "  ]\n";
            $text .= "}\n";
            return $text;
        }

        // Otherwise ENCODE_AS_OBJECT_WITH_SCHEMA (default)
        $text  = "{\n";
        $text .= $this->_encodeTableObjectHeader( '  ', $table );
        $text .= "  \"fields\": [\n";
        $text .= $this->_encodeTableColumnsAsObjects( '    ', $table );
        $text .= "  ],\n";
        $text .= "  \"table\": [\n";
        $text .= $this->_encodeTableRowsAsObjects( '    ', $table );
        $text .= "  ]\n";
        $text .= "}\n";
        return $text;
    }





    /**
     * Encodes a table's information as name:value pairs
     * for a header object.
     *
     * The returned text has the form:
     * <pre>
     *      "name": "shortName",
     *      "title": "longName",
     *      "description": "description",
     *      "type": "schema name",
     * </pre>
     *
     * @param  string  $indent  a string containing the indentation
     * string (presumably just spaces) the prefix every row of
     * encoded text generated by this method.
     *
     * @param  Table  $table  the table who's attributes are being
     * encoded into the returned JSON text.
     *
     * @return  string  the JSON text that encodes the table's
     * attributes.
     */
    private function _encodeTableObjectHeader( $indent, &$table )
    {
        // Get attributes. Any of these may be NULL.
        $attributes = $table->getAttributes( );

        $name = '';
        if ( isset( $attributes['name'] ) )
            $name = $attributes['name'];

        $title = '';
        if ( isset( $attributes['longName'] ) )
            $title = $attributes['longName'];

        $description = '';
        if ( isset( $attributes['description'] ) )
            $description = $attributes['description'];

        $type = '';
        if ( isset( $attributes['sourceSchemaName'] ) )
            $type = $attributes['sourceSchemaName'];


        $text = '';
        if ( $name !== '' )
            $text .= "$indent\"name\": \"$name\",\n";

        if ( $title !== '' )
            $text .= "$indent\"title\": \"$title\",\n";

        if ( $description !== '' )
            $text .= "$indent\"description\": \"$description\",\n";

        if ( $type !== '' )
            $text .= "$indent\"type\": \"$type\",\n";

        return $text;
    }





    /**
     * Encodes a table's column information as a table
     * schema array of field objects.
     *
     * The returned text has the form:
     * <pre>
     *    {
     *      "name": [ "shortName" ],
     *      "title": [ "longName" ],
     *      "description": [ "description" ],
     *      "type": [ "dataType" ],
     *      "format": [ "default" ]
     *    },
     *    {
     *      ...etc...
     *    }
     * </pre>
     *
     * @param  string  $indent  a string containing the indentation
     * string (presumably just spaces) the prefix every row of
     * encoded text generated by this method.
     *
     * @param  Table  $table  the table who's columns are being
     * encoded into the returned JSON text.
     *
     * @return  string  the JSON text that encodes the table's
     * column attributes.
     */
    private function _encodeTableColumnsAsObjects( $indent, &$table )
    {
        $nColumns = $table->getNumberOfColumns( );
        $text = '';

        for ( $i = 0; $i < $nColumns; ++$i )
        {
            // Get column information. There must be a short name,
            // but the long name and description may be empty.
            $attributes = $table->getColumnAttributes( $i );

            $name = '';
            if ( isset( $attributes['name'] ) )
                $name = $attributes['name'];

            $title = '';
            if ( isset( $attributes['longName'] ) )
                $title = $attributes['longName'];

            $description = '';
            if ( isset( $attributes['description'] ) )
                $description = $attributes['description'];

            $type = '';
            if ( isset( $attributes['type'] ) )
                $type = $attributes['type'];

            $format = 'default';

            // Add the column object.
            $text .= "$indent{\n";

            $text .= "$indent  \"name\": [ \"$name\" ],\n";
            if ( $title !== '' )
                $text .= "$indent  \"title\": [ \"$title\" ],\n";

            if ( $description !== '' )
                $text .= "$indent  \"description\": [ \"$description\" ],\n";

            if ( $type !== '' )
                $text .= "$indent  \"type\": [ \"$type\" ],\n";

            $text .= "$indent  \"format\": [ \"$format\" ]\n";

            if ( $i != ($nColumns-1) )
                $text .= "$indent},\n";
            else
                $text .= "$indent}\n";
        }
        return $text;
    }





    /**
     * Encodes a table's column names as an array of values.
     *
     * The returned text has the form:
     * <pre>
     *  [ "Name 1", "Name 2", "Name 3", ... ],
     * </pre>
     *
     * @param  string  $indent  a string containing the indentation
     * string (presumably just spaces) the prefix every row of
     * encoded text generated by this method.
     *
     * @param  Table  $table  the table who's columns are being
     * encoded into the returned JSON text.
     *
     * @return  string  the JSON text that encodes the table's
     * column attributes.
     */
    private function _encodeTableColumnsAsArray( $indent, &$table )
    {
        $nRows    = $table->getNumberOfRows( );
        $nColumns = $table->getNumberOfColumns( );

        // Create a single line of text with the column names
        // quoted, comma separated, and surrounded by square
        // brackets.
        $text = $indent . '[ ';
        for ( $i = 0; $i < $nColumns; $i++ )
        {
            if ( $i != 0 )
                $text .= ', ';

            // Get the column name.
            $name = $table->getColumnName( $i );

            // Add it as a quoted string.
            $text .= '"' . $name . '"';
        }

        if ( $nRows > 0 )
            $text .= " ],\n";
        else
            $text .= " ]\n";
        return $text;
    }





    /**
     * Encodes the given table's rows, with each row encoded as an
     * array of comma-separated values.
     *
     * The returned text has the form:
     * <pre>
     *  [ 1, 2, 3 ],
     *  [ 4, 5, 6 ],
     *  ...
     * </pre>
     *
     * @param  string  $indent  a string containing the indentation
     * string (presumably just spaces) the prefix every row of
     * encoded text generated by this method.
     *
     * @param  Table  $table  the table who's rows are being
     * encoded into the returned JSON text.
     *
     * @return  string  the JSON text that encodes the table's rows.
     */
    private function _encodeTableRowsAsArrays( $indent, &$table )
    {
        $nRows    = $table->getNumberOfRows( );
        if ( $nRows == 0 )
            return '';
        $nColumns = $table->getNumberOfColumns( );
        $text = '';

        for ( $row = 0; $row < $nRows; $row++ )
        {
            // Put each table row on one line, surrounded
            // by square brackets, and separated by commas.
            // Double-quote anything that isn't a simple
            // scalar.
            $text .= $indent . '[ ';
            for ( $i = 0; $i < $nColumns; $i++ )
            {
                if ( $i != 0 )
                    $text .= ', ';

                // Get the value.
                $v = $table->getValue( $row, $i );

                // Add it as a simple scalar or a
                // quoted string.
                if ( is_int( $v ) ||
                    is_float( $v ) )
                    $text .= $v;
                else if ( is_bool( $v ) )
                {
                    if ( $v === true )
                        $text .= 'true';
                    else
                        $text .= 'false';
                }
                else if ( is_null( $v ) )
                    $text .= 'null';
                else
                    $text .= '"' . $v . '"';
            }

            if ( $row != $nRows-1 )
                $text .= " ],\n";
            else
                $text .= " ]\n";
        }
        return $text;
    }





    /**
     * Encodes the given table's rows, with each row encoded as an
     * object with name:value pairs using the column names.
     *
     * The returned text has the form:
     * <pre>
     *  { "Name 1": 1, "Name 2": 2, "Name 3": 3, ... },
     *  { "Name 1": 4, "Name 2": 5, "Name 3": 6, ... }
     * </pre>
     *
     * @param  string  $indent  a string containing the indentation
     * string (presumably just spaces) the prefix every row of
     * encoded text generated by this method.
     *
     * @param  Table  $table  the table who's rows are being
     * encoded into the returned JSON text.
     *
     * @return  string  the JSON text that encodes the table's rows.
     */
    private function _encodeTableRowsAsObjects( $indent, &$table )
    {
        $nRows = $table->getNumberOfRows( );
        if ( $nRows == 0 )
            return '';
        $nColumns = $table->getNumberOfColumns( );
        $text = '';

        for ( $row = 0; $row < $nRows; $row++ )
        {
            // Put each table row on one line, surrounded
            // by curly braces, and separated by commas.
            // Use name:value pairs for each column.
            // Double-quote anything that isn't a simple
            // scalar.
            $text .= "$indent{ ";
            for ( $i = 0; $i < $nColumns; $i++ )
            {
                if ( $i != 0 )
                    $text .= ', ';

                // Get the value and column name.
                $v = $table->getValue( $row, $i );
                $name = $table->getColumnName( $i );

                // Add it as a simple scalar or a
                // quoted string.
                $text .= '"' . $name . '": ';
                if ( is_int( $v ) ||
                    is_float( $v ) )
                    $text .= $v;
                else if ( is_bool( $v ) )
                {
                    if ( $v === true )
                        $text .= 'true';
                    else
                        $text .= 'false';
                }
                else if ( is_null( $v ) )
                    $text .= 'null';
                else
                    $text .= '"' . $v . '"';
            }

            if ( $row != $nRows-1 )
                $text .= " },\n";
            else
                $text .= " }\n";
        }
        return $text;
    }
    // @}
}

/**
 * @file
 * Defines SDSC\StructuredData\Format\JSONTreeFormat to parse and
 * serialize data in the JSON (JavaScript Object Notation) text syntax
 * for trees.
 */

namespace SDSC\StructuredData\Format;


use SDSC\StructuredData\Tree;





/**
 * @class JSONTreeFormat
 * JSONTreeFormat provides decode and encode functions that map
 * between JSON (JavaScript Object Notation) text and a
 * \SDSC\StructuredData\Tree.
 *
 * JSON is a general-purpose syntax for describing objects, arrays,
 * scalars, and arrays of objects, arrays, of scalars to an arbitrary
 * nesting depth. This class, however, focuses on a narrower subset of
 * JSON usage in order to build trees.
 *
 *
 * #### Tree syntax
 * A JSON tree is a hierarchy of nodes starting with a root node
 * with a name and list of children. Each of those children nodes
 * has a name and their own list of children, and so on to arbitrary
 * depth. Any node can have any number of named attributes with
 * arbitrary values.
 *
 *
 * ##### Tree object
 * JSON trees always start as an object. The object is expected to
 * have a "name" property and a "children" property, but both of
 * these are optional.  The name may be a scalar string or an array
 * with at least one scalar string value. Non-string values are
 * silently converted to strings.
 * <pre>
 *  {
 *      "name": "something",
 *      "children": [ ... ]
 *  }
 * </pre>
 * or
 * <pre>
 *  {
 *      "name": [ "something" ],
 *      "children": [ ... ]
 *  }
 * </pre>
 *
 * Each item in the "children" array is another node object with an
 * optional "name" property and a "children" property with another
 * nested array of node objects, and so on.
 *
 *
 * ##### Parent object
 * JSON trees can be included in a parent object within a "tree"
 * property (see ENCODE_AS_OBJECT):
 * <pre>
 *  {
 *      "tree": [ ... ]
 *  }
 * </pre>
 *
 *
 * ##### Tree names
 * JSON trees within a parent object may have additional properties
 * that give the tree's short name (name), long name (title), and
 * description.  The name, title, and description property values may
 * be a scalar string or an array with at least one scalar string value.
 * Non-string values are silently converted to strings.
 * <pre>
 *  {
 *      "name":  [ "tbl" ],
 *      "title": [ "Big tree" ],
 *      "description": [ "A big tree with lots of data" ],
 *      "tree":  [ ... ]
 *  }
 * </pre>
 *
 *
 * ##### Tree schema name
 * JSON trees can have a microformat schema name that refers to
 * a well-known schema by setting the "type" property of the parent
 * object.  The type property value may be an array or a scalar with a
 * single string value.
 * <pre>
 *  {
 *      "type": [ "json-tree" ],
 *      "tree": [ ... ]
 *  }
 * </pre>
 *
 *
 * #### Tree decode limitations
 * The amount of tree and node descriptive information available
 * in a JSON file depends upon how much of syntax above is used.
 * While trees and nodes should have names, for instance, these are
 * optional. Descriptions and other metadata are also optional.
 *
 *
 * #### Tree encode limitations
 * The encoder can output trees in several JSON syntax forms.
 *
 *
 * @see     SDSC\StructuredData\Tree    the StructuredData Tree class
 *
 * @author  David R. Nadeau / University of California, San Diego
 *
 * @date    1/27/2016
 *
 * @since   0.0.1
 *
 * @version 0.0.1  Initial development.
 *
 * @version 0.0.2  Revised to provide format attributes per RDA, and to
 * create tables using the updated Table API that uses an array of attributes.
 *
 * @version 0.0.3  Moved Table, Tree, and Graph handling into separate classes.
 */
final class JSONTreeFormat
    extends AbstractFormat
{
//----------------------------------------------------------------------
// Constants
//----------------------------------------------------------------------
    /**
     * An encoding style that generates a single object that starts
     * immediately with the root node. This is the most basic form
     * of tree output and omits a tree name and other tree metadata.
     * Node names, long names, descriptions, and other metadata are
     * included.
     *
     * <pre>
     * {
     *   "name": "node short name",
     *   "title": "node long name"
     *   "description": "node description",
     *   "children": [ ... ]
     * }
     * </pre>
     */
    const ENCODE_AS_OBJECT = 1;

    /**
     * An encoding style identical to ENCODE_AS_OBJECT, but with
     * a parent object that includes the tree's metadata and schema.
     *
     * This is the default encoding.
     *
     * <pre>
     * {
     *   "name": "tree short name",
     *   "title": "tree long name"
     *   "description": "tree description",
     *   "type": "tree source schema name",
     *   "tree": [
     *     "name": "node short name",
     *     "title": "node long name"
     *     "description": "node description",
     *     "children": [ ... ]
     *   ]
     * }
     * </pre>
     */
    const ENCODE_AS_OBJECT_WITH_SCHEMA = 2;





//----------------------------------------------------------------------
// Constructors & Destructors
//----------------------------------------------------------------------
    /**
     * @name Constructors
     */
    // @{
    /**
     * Constructs and returns a new format object that may be used to
     * decode and encode trees in JSON (JavaScript Object Notation).
     */
    public function __construct( )
    {
        parent::__construct( );

        $this->attributes['syntax']         = 'JSON';
        $this->attributes['name']           = 'json-tree';
        $this->attributes['longName']       = 'JavaScript Object Notation (JSON) Tree';
        $this->attributes['MIMEType']       = 'application/json';
        $this->attributes['fileExtensions'] = array( 'json' );
        $this->attributes['description'] =
            'The JSON (JavaScript Object Notation) format encodes ' .
            'a variety of data, including tables, trees, and graphs. '.
            'Tree data may have an unlimited number of nodes arranged ' .
            'in a hierarchy starting with a root node. Each node may have ' .
            'children, and those may have children. Every node may have a ' .
            'a short name, long name, and description, and any number and ' .
            'type of named values.';
        $this->attributes['expectedUses'] = array(
            'Trees with parent and child names with names and values'
        );
        $this->attributes['standards'] = array(
            array(
                'issuer' => 'RFC',
                'name' => 'IETF RFC 7159',
                'natureOfApplicability' => 'specifies',
                'details' => 'The JavaScript Object Notation (JSON) Data Interchange Format'
            ),
            array(
                'issuer' => 'ad hoc',
                'name' => 'JSON Tree',
                'natureOfApplicability' => 'specifies',
                'details' => ''
            )
        );

        // Unknown:
        //  identifier
        //  creationDate
        //  lastModificationDate
        //  provenance
    }
    // @}

    /**
     * @name Destructors
     */
    // @{
    /**
     * Destroys a previously-constructed format object.
     */
    public function __destruct( )
    {
        parent::__destruct( );
    }
    // @}





//----------------------------------------------------------------------
// Encode/decode attribute methods
//----------------------------------------------------------------------
    /**
     * @name Encode/decode attribute methods
     */
    // @{
    /**
     * @copydoc AbstractFormat::getComplexity
     */
    public function getComplexity( )
    {
        return 10;
    }

    /**
     * @copydoc AbstractFormat::canDecodeTrees
     */
    public function canDecodeTrees( )
    {
        return true;
    }

    /**
     * @copydoc AbstractFormat::canEncodeTrees
     */
    public function canEncodeTrees( )
    {
        return true;
    }
    // @}





//----------------------------------------------------------------------
// Decode methods
//----------------------------------------------------------------------
    /**
     * @name Decode methods
     */
    // @{
    /**
     * @copydoc AbstractFormat::decode
     *
     * #### Decode limitations
     * The JSON format always returns an array containing a single
     * SDSC\StructuredData\Tree object.
     */
    public function decode( &$text )
    {
        // Parse JSON
        // -----------------------------------------------------
        //   Parse JSON text.
        if ( empty( $text ) )
            return array( );        // No tree

        // Passing 'false' to json_decode( ) means that it should *not*
        // silently convert objects into arrays. We need to know whether
        // something in the text is an object or array because they have
        // different meanings and different parse paths below.
        $content = json_decode( $text, false );
        if ( $content == NULL )
        {
            // Failure to parse.
            $code = json_last_error( );
            switch ( $code )
            {
            case JSON_ERROR_STATE_MISMATCH:
            case JSON_ERROR_SYNTAX:
                throw new SyntaxException(
                    'Malformed JSON. Problem with commas, brackets, or parenthesis?' );
            case JSON_ERROR_CTRL_CHAR:
            case JSON_ERROR_UTF8:
                throw new SyntaxException(
                    'Malformed JSON. Control characters or bad UTF-8?' );

            // The maximum nesting depth is not defined by PHP and may
            // vary with changes in the implementation. This makes unit
            // testing for this case is not practical, so we ignore it.
            // @codeCoverageIgnoreStart
            case JSON_ERROR_DEPTH:
                throw new SyntaxException(
                    'Malformed JSON. Nesting too deep.' );
            // @codeCoverageIgnoreEnd
            }

            // There is no content, and yet we don't know what the
            // error is, if any.
            throw new SyntaxException(
                'Malformed JSON.' );
        }
        // At this point we don't know what type of content we have.
        // We could have a tree.


        // Determine content type
        // -----------------------------------------------------
        // If the content is an object, look for a few
        // tell-tale properties to see what we have.
        if ( is_object( $content ) )
        {
            // Possabilities:
            //  'children' - definitely a tree
            //  'tree'     - definitely a tree
            //
            // Reject anything that isn't a tree.
            if ( property_exists( $content, 'tree' ) )
            {
                // When there's a 'tree' property, we have a top-level
                // object that is a tree and it may have a schema.
                return $this->_decodeTreeObjectWithSchema( $content );
            }
            if ( property_exists( $content, 'children' ) )
            {
                // When there's a 'children' property, we have a top-level
                // object that is the root node and there is no schema.
                return $this->_decodeTreeObject( $content );
            }
        }

        // Otherwise we don't know what it is.
        throw new SyntaxException(
            'Unrecognized JSON content. Does not appear to be a tree.' );
    }

    /**
     * Decodes a tree object in the JSON Tree format used by the d3
     * visualization library with a header and schema giving
     * tree attributes.
     *
     * @param array $content  the content
     *
     * @throws InvalidContentException if the content cannot be parsed
     */
    private function _decodeTreeObjectWithSchema( &$content )
    {
        // Check the type
        // -----------------------------------------------------
        // The format has a schema type and a tree.  The schema
        // type must be recognized.
        //
        // Good example:
        //  {
        //      "type":  [ "json-tree" ],
        //      "name":  [ "my tree" ],
        //      "title": [ "Big tree" ],
        //      "description": [ "A big tree with lots of data" ],
        //      "tree":  { ... }
        //  }

        if ( property_exists( $content, 'type' ) )
        {
            $type = $content->type;
            if ( !is_scalar( $type ) || (string)$type != 'json-tree' )
                throw new InvalidContentException(
                    'JSON tree "type" must be "json-tree".' );
        }


        // Parse attributes
        // -----------------------------------------------------
        // Get all of a tree's top-level attributes. Confirm usage
        // for well-known attributes.
        $attributes = $this->_decodeAttributes( $content );

        // Add standard attributes, overriding anything in the input.
        $attributes['sourceMIMEType']   = $this->getMIMEType( );
        $attributes['sourceSyntax']     = $this->getSyntax( );
        $attributes['sourceSchemaName'] = 'json-tree';



        // Create tree
        // -----------------------------------------------------
        // Create the empty tree with the attributes, then find
        // and parse the root and all of its children.
        $tree = new Tree( $attributes );

        if ( property_exists( $content, 'tree' ) )
        {
            $root = $content->tree;
            if ( !is_object( $root ) )
                throw new InvalidContentException(
                    'JSON "tree" property must be an object for the tree root.' );
            $this->_decodeTreeRoot( $tree, $root );
        }

        return array( $tree );
    }

    /**
     * Decodes a tree object without a schema.
     *
     * @param array $content  the content
     *
     * @throws InvalidContentException if the content cannot be parsed
     */
    private function _decodeTreeObject( &$content )
    {
        // Create tree
        // -----------------------------------------------------
        // The format does not support a tree name or description
        // and starts immediately with the root node.
        //
        // Good example:
        //  {
        //      "name":  "root",
        //      "children":  [ ... ]
        //  }
        $attributes = array(
            // 'name' unknown
            // 'longName' unknown
            // 'description' unknown
            // 'sourceFileName' unknown
            'sourceMIMEType'   => $this->getMIMEType( ),
            'sourceSyntax'     => $this->getSyntax( ),
            'sourceSchemaName' => 'json-tree'
        );
        $tree = new Tree( $attributes );


        // Parse root and children
        // -----------------------------------------------------
        if ( !empty( $content ) )
            $this->_decodeTreeRoot( $tree, $content );

        return array( $tree );
    }

    /**
     * Decodes a tree node.
     *
     * @param Tree  $tree     the empty tree
     *
     * @param array $content  the content
     *
     * @throws InvalidContentException if the content cannot be parsed
     */
    private function _decodeTreeRoot( &$tree, &$content )
    {
        // Parse attributes
        // -----------------------------------------------------
        // The format has a node name, description, etc.
        //
        // Good example:
        //  {
        //      "name":        [ "root" ],
        //      "title":       [ "Root-er-iffic" ],
        //      "description": [ "A big root" ],
        //      "children":    [ ... ]
        //  }

        // Get all of a node's attributes. Confirm usage
        // for well-known attributes.
        $attributes = $this->_decodeAttributes( $content );


        // Create root
        // -----------------------------------------------------
        $rootNodeId = $tree->setRootNode( $attributes );


        // Create children
        // -----------------------------------------------------
        if ( property_exists( $content, 'children' ) )
        {
            $children = $content->children;
            if ( !is_array( $children ) )
                throw new InvalidContentException(
                    'JSON "children" property must be an array of child nodes.' );

            $this->_recursivelyDecodeTreeChildren( $tree,
                $rootNodeId, $children );
        }
    }

    /**
     * Recursively decodes the given array of children objects,
     * adding them as children to the selected parent node.
     *
     * @param Tree    $tree          the tree to add further nodes to.
     *
     * @param integer $parentNodeId  the unique positive numeric ID of
     * the parent node.
     *
     * @param array $childrenToAdd  an array of children objects to add to
     * the parent node.
     *
     * @throws InvalidContentException  if any 'children' property is
     * not an array of objects.
     *
     * @throws InvalidContentException  if any 'name' property is not
     * a scalar string.
     */
    private function _recursivelyDecodeTreeChildren( &$tree,
        $parentNodeId, &$childrenToAdd )
    {
        foreach ( $childrenToAdd as &$child )
        {
            // Parse attributes
            // -------------------------------------------------
            // The format has a node name, description, etc.
            //
            // Good example:
            //  {
            //      "name":        [ "root" ],
            //      "title":       [ "Root-er-iffic" ],
            //      "description": [ "A big root" ],
            //      "children":    [ ... ]
            //  }

            // Get all of a node's attributes. Confirm usage
            // for well-known attributes.
            $attributes = $this->_decodeAttributes( $child );


            // Add child
            // -------------------------------------------------
            $childID = $tree->addNode( $parentNodeId, $attributes );


            // Create children
            // -------------------------------------------------
            if ( property_exists( $child, 'children' ) )
            {
                $children = $child->children;
                if ( !is_array( $children ) )
                    throw new InvalidContentException(
                        'JSON "children" property must be an array of child nodes.' );

                $this->_recursivelyDecodeTreeChildren( $tree,
                    $childID, $children );
            }
        }
    }

    /**
     * Decodes attributes for a tree or node and returns an associative
     * array containing those attributes.
     *
     * @param array $content  the content.
     *
     * @return array  the associative array of decoded attributes.
     *
     * @throws InvalidContentException if the content cannot be parsed.
     */
    private function _decodeAttributes( &$content )
    {
        // Create attributes
        // -----------------------------------------------------
        // The format supports "name", "title", and "description"
        // well-known attributes. Additional attributes may be
        // added by the user.
        //
        // Good example:
        //  {
        //      "name":        "Node123",
        //      "title":       "Cool node",
        //      "description": "This is a cool node",
        //      "whatever":    "something"
        //  }
        //
        // Well-known attribute values should be scalar strings,
        // but we'll accept an array with at least one entry and
        // use the first entry as the value.

        // Convert the object to an attributes array that initially
        // contains all properties. We'll type check and clean things
        // out below.
        $attributes = get_object_vars( $content );

        // Get rid of attributes we handle separately.
        if ( isset( $attributes['tree'] ) )
            unset( $attributes['tree'] );

        if ( isset( $attributes['children'] ) )
            unset( $attributes['children'] );


        // Name, Title, and Description
        // -----------------------------------------------------
        // If these exists, make sure they are a string.  Rename
        // attributes to use our internal attribute names.
        if ( isset( $attributes['name'] ) )
        {
            $value = $attributes['name'];
            if ( is_array( $value ) && count( $value ) > 0 )
                $attributes['name'] = (string)$value[0];
            else if ( is_scalar( $value ) )
                $attributes['name'] = (string)$value;
            else
                throw new InvalidContentException(
                    'JSON tree "name" property must be a scalar string.' );
        }
        if ( isset( $attributes['title'] ) )
        {
            // Rename 'title' to 'longName'
            $value = $attributes['title'];
            unset( $attributes['title'] );
            if ( is_array( $value ) && count( $value ) > 0 )
                $attributes['longName'] = (string)$value[0];
            else if ( is_scalar( $value ) )
                $attributes['longName'] = (string)$value;
            else
                throw new InvalidContentException(
                    'JSON tree "title" property must be a scalar string.' );
        }
        if ( isset( $attributes['description'] ) )
        {
            $value = $attributes['description'];
            if ( is_array( $value ) && count( $value ) > 0 )
                $attributes['description'] = (string)$value[0];
            else if ( is_scalar( $value ) )
                $attributes['description'] = (string)$value;
            else
                throw new InvalidContentException(
                    'JSON tree "description" property must be a scalar string.' );
        }

        return $attributes;
    }





//----------------------------------------------------------------------
// Encode methods
//----------------------------------------------------------------------
    /**
     * @name Encode methods
     */
    // @{
    /**
     * @copydoc AbstractFormat::encode
     *
     * #### Encode limitations
     * The JSON format only supports encoding a single
     * SDSC\StructuredData\Tree to the format.
     */
    public function encode( &$objects, $options = 0 )
    {
        //
        // Validate arguments
        // -----------------------------------------------------
        if ( empty( $objects ) )
            return NULL;            // No tree to encode
        if ( !is_array( $objects ) )
            throw new \InvalidArgumentException(
                'JSON encode requires an array of objects.' );
        if ( count( $objects ) > 1 )
            throw new \InvalidArgumentException(
                'JSON encode only supports encoding a single object.' );
        $object = &$objects[0];

        //
        // Encode
        // -----------------------------------------------------
        if ( is_a( $object, 'SDSC\StructuredData\Tree', false ) )
            return $this->_encodeTree( $object, $options );
        else
            throw new \InvalidArgumentException(
                'JSON encode object must be a tree.' );
    }

    /**
     * Encodes the given tree as JSON text, controlled by the given
     * options.
     *
     * @param  Tree   $tree  the tree object to be encoded.
     *
     * @param integer  $options  encoding options to control how
     * JSON text is generated.
     *
     * @return  string        the JSON text that encodes the tree.
     */
    private function _encodeTree( &$tree, $options )
    {
        if ( $tree->getNumberOfNodes( ) == 0 )
            return '';              // Empty tree

        if ( $options == self::ENCODE_AS_OBJECT )
            return $this->_encodeAsObject( $tree );

        // Otherwise ENCODE_AS_OBJECT_WITH_SCHEMA (default)
        return $this->_encodeAsObjectWithSchema( $tree );
    }


    /**
     * Encodes the given tree as an object, starting immediately with
     * the root node and without including any tree attributes.
     *
     * @param  Tree    $tree    the tree object to be encoded.
     *
     * @return  string          the JSON text that encodes the tree.
     */
    private function _encodeAsObject( &$tree )
    {
        return $this->_recursivelyEncodeTree( $tree, '',
            $tree->getRootNodeID( ) );
        $text .= "\n";
        return $text;
    }

    /**
     * Encodes the given tree as an object, starting with a header
     * that includes the tree's attributes, followed by a "tree"
     * property that includes the root node and all of its children.
     *
     * @param  Tree    $tree    the tree object to be encoded.
     *
     * @return  string          the JSON text that encodes the tree.
     */
    private function _encodeAsObjectWithSchema( &$tree )
    {
        $attributes = $tree->getAttributes( );

        $name = NULL;
        if ( isset( $attributes['name'] ) )
            $name = $attributes['name'];

        $title = NULL;
        if ( isset( $attributes['longName'] ) )
            $title = $attributes['longName'];

        $description = NULL;
        if ( isset( $attributes['description'] ) )
            $description = $attributes['description'];

        $type = NULL;
        if ( isset( $attributes['sourceSchemaName'] ) )
            $type = $attributes['sourceSchemaName'];

        $indent = '  ';
        $text   = "{\n";

        // Header
        if ( !empty( $name ) )
            $text .= $indent . '"name": "' . $name . '",' . "\n";
        if ( !empty( $title ) )
            $text .= $indent . '"title": "' . $title . '",' . "\n";
        if ( !empty( $description ) )
            $text .= $indent . '"description": "' . $description . '",' . "\n";
        if ( !empty( $type ) )
            $text .= $indent . '"type": "' . $type . '",' . "\n";

        // Tree
        $text .= $indent . '"tree":';
        $text .= $this->_recursivelyEncodeTree( $tree, $indent,
            $tree->getRootNodeID( ) );
        $text .= "\n}\n";
        return $text;
    }

    /**
     * Recursively encodes the given tree, starting at the selected node,
     * and indenting each line with the given string.
     *
     * @param  Tree    $tree    the tree object to be encoded.
     *
     * @param  string  $indent  the text string to prepend to every line
     * of encoded text.
     *
     * @param  integer $nodeId  the unique positive numeric ID of the tree
     * node to encode, along with all of its children.
     */
    private function _recursivelyEncodeTree( &$tree, $indent, $nodeId )
    {
        // Add all attributes.
        $text = $indent . "{\n";
        $endofline = '';
        foreach ( $tree->getNodeAttributes( $nodeId ) as $key => $value )
        {
            $text .= $endofline;

            if ( is_int( $value ) || is_float( $value ) || is_bool( $value ) )
                $text .= "$indent  \"$key\": $value";

            else if ( is_null( $value ) )
                $text .= "$indent  \"$key\": null";

            else if ( is_string( $value ) )
                $text .= "$indent  \"$key\": \"$value\"";

            else if ( is_object( $value ) || is_array( $value ) )
            {
                // Don't know what this is, so encode it blind.
                $text .= "$indent  \"$key\": " . json_encode( $value );
            }

            $endofline = ",\n";
        }

        // Add children.
        $children = $tree->getNodeChildren( $nodeId );
        if ( !empty( $children ) )
        {
            $text .= $endofline;
            $text .= "$indent  \"children\": [\n";
            $indent2 = $indent . '    ';
            for ( $i = 0; $i < count( $children ); $i++ )
            {
                if ( $i != 0 )
                    $text .= ",\n";
                $text .= $this->_recursivelyEncodeTree(
                    $tree, $indent2, $children[$i] );
            }
            $text .= "\n$indent  ]\n";
        }
        else if ( $nodeId == $tree->getRootNodeID( ) )
        {
            // Include an empty 'children' array for the root node
            // because it helps identify the text as in the tree format.
            $text .= $endofline;
            $text .= "$indent  \"children\": [ ]\n";
        }
        else
            $text .= "\n";
        $text .= $indent . '}';

        return $text;
    }
    // @}
}

/**
 * @file
 * Defines SDSC\StructuredData\Format\SyntaxException to report that a syntax
 * error occurred while trying to parse content.
 */

namespace SDSC\StructuredData\Format;






/**
 * @class SyntaxException
 * SyntaxException describes an exception thrown when an error occurs
 * while trying to parse content based upon syntactic rules.
 *
 * Typical errors include:
 * - Empty content
 * - Missing comma, brace, bracket, quote, space, etc.
 * - Unexpected end to content
 *
 *
 * @author  David R. Nadeau / University of California, San Diego
 *
 * @date    2/10/2016
 *
 * @since   0.0.1
 *
 * @version 0.0.1  Initial development.
 */
class SyntaxException
    extends FormatException
{
//----------------------------------------------------------------------
// Constructors & Destructors
//----------------------------------------------------------------------
    /**
     * @name Constructors
     */
    // @{
    /**
     * Constructs and returns a new exception object.
     *
     * @param string $message  the exception message
     *
     * @param int $code        the exception code
     *
     * @param int $severity    the severity level
     *
     * @param string $filename the filename where the exception was created
     *
     * @param int $lineno      the line where the exception was created
     *
     * @param Exception $previous the previous exception, if any
     */
    public function __construct(
        $message  = "",
        $code     = 0,
        $severity = 1,
        $filename = __FILE__,
        $lineno   = __LINE__,
        \Exception $previous = NULL )
    {
        parent::__construct( $message, $code, $severity,
            $filename, $lineno, $previous );
    }
    // @}

    /**
     * @name Destructors
     */
    // @{
    /**
     * Destroys a previously-constructed object.
     */
    public function __destruct( )
    {
        parent::__destruct( );
    }
    // @}
}


/**
 * @file
 * Defines SDSC\StructuredData\Format\TSVTableFormat to parse and
 * serialize data in the Tab-Separated Value (TSV) text format.
 */

namespace SDSC\StructuredData\Format;


use SDSC\StructuredData\Table;





/**
 * @class TSVTableFormat
 * TSVTableFormat provides decode and encode functions that map between
 * Tab-Separated Values (TSV) text and a Table.
 *
 *
 * #### Table Syntax
 * TSV is a de facto standard and only loosely documented.
 *
 * A TSV file contains a header and zero or more records.  The header and
 * records are each terminated by CRLF (carriage-return linefeed).
 *
 * The header and each record is a list of fields, separated by tabs. The
 * fields in a header are presumed to be column names.
 *
 * The TSV format is a de facto standard but is not documented by any
 * format specification.  A TSV file contains a single table made up
 * of a list of records written as lines in a TSV text file. Records are
 * separated by CRLF (carriage-return and linefeed) pairs, in that order.
 * A common and supported variant is to use a linfeed alone as a record
 * delimiter (typical on Linux and OS X).
 *
 * Values in each record are separated by TABs. Values may be numbers,
 * strings, and arbitrary multi-word tokens. Values are not surrounded
 * by quotes, or any other delimiters.
 *
 * The first record in a TSV file provides the names for table columns.
 * All further records provide table data. Every record must have the
 * same number of values.
 *
 *
 * #### Table decode limitations
 * TSV does not provide descriptive information beyond table column
 * names. The returned table uses these TSV names as column short names,
 * but leaves column long names, descriptions, and data types empty.
 * The returned table's own short name, long name, and description are
 * also left empty.
 *
 * Since the TSV syntax does not provide data types for column values,
 * these data types are automatically inferred by the
 * SDSC\StructuredData\Table class. That class scans through each column and
 * looks for a consistent interpretation of the values as integers,
 * floating-point numbers, booleans, etc., then sets the data type
 * accordingly.
 *
 *
 * #### Table encode limitations
 * Since TSV does not support descriptive information for the table,
 * the table's short name, long name, and description are not included
 * in the encoded text.
 *
 * Since TSV only supports a single name for each column, the table's
 * column short names are output to the encoded text, but the column
 * long names, descriptions, and data types are not included.
 *
 *
 * @see     SDSC\StructuredData\Table   the StructuredData Table class
 *
 * @author  David R. Nadeau / University of California, San Diego
 *
 * @date    9/24/2018
 *
 * @since   0.0.1
 *
 * @version 0.0.1  Initial development.
 *
 * @version 0.0.2  Revised to provide format attributes per RDA, and to
 * create tables using the updated Table API that uses an array of attributes.
 *
 * @version 0.0.3. Revised to insure that CR-LF, LF-CR, CR alone, and LF alone
 * as line endings/delimiters are all accepted.
 */
final class TSVTableFormat
    extends AbstractFormat
{
//----------------------------------------------------------------------
// Constructors & Destructors
//----------------------------------------------------------------------
    /**
     * @name Constructors
     */
    // @{
    /**
     * Constructs and returns a new format object that may be used to
     * decode and encode tables in TSV.
     */
    public function __construct( )
    {
        parent::__construct( );

        $this->attributes['syntax']         = 'TSV';
        $this->attributes['name']           = 'TSV';
        $this->attributes['longName']       = 'Tab-Separated Values (TSV)';
        $this->attributes['fileExtensions'] = array( 'tsv', 'txt' );
        $this->attributes['MIMEType']       = 'text/tab-separated-values';
        $this->attributes['description'] =
            'The TSV (Tab-Separated Values) format encodes tabular data ' .
            'with an unlimited number of rows and columns. Each column has ' .
            'a short name. All rows have a value for every column. Row ' .
            'values are typically integers or floating-point numbers, but ' .
            'they also may be strings and booleans.';
        $this->attributes['expectedUses'] = array(
            'Tabular data with named columns and rows of values' );

        // Unknown:
        //  identifier
        //  creationDate
        //  lastModificationDate
        //  provenance
        //  standards (none)
    }
    // @}

    /**
     * @name Destructors
     */
    // @{
    /**
     * Destroys a previously-constructed format object.
     */
    public function __destruct( )
    {
        parent::__destruct( );
    }
    // @}





//----------------------------------------------------------------------
// Encode/decode attributes methods
//----------------------------------------------------------------------
    /**
     * @name Encode/decode attributes methods
     */
    // @{
    /**
     * @copydoc AbstractFormat::getComplexity
     */
    public function getComplexity( )
    {
        return 0;
    }

    /**
     * @copydoc AbstractFormat::canDecodeTables
     */
    public function canDecodeTables( )
    {
        return true;
    }

    /**
     * @copydoc AbstractFormat::canEncodeTables
     */
    public function canEncodeTables( )
    {
        return true;
    }
    // @}





//----------------------------------------------------------------------
// Encode/decode methods
//----------------------------------------------------------------------
    /**
     * @name Encode/decode methods
     */
    // @{
    /**
     * @copydoc AbstractFormat::decode
     *
     * #### Decode limitations
     * The TSV format always returns an array containing a single
     * SDSC\StructuredData\Table object.
     */
    public function decode( &$text )
    {
        if ( empty( $text ) )
            return array( );        // No table

        //
        // Preprocess
        // -----------------------------------------------------
        //   Change all CR-LF, LF-CR, CR alone, or LF alone into LF.
        //
        //   Remove the last LF, if any, so that exploding on LF
        //   doesn't leave us an extra empty line at the end.
        $newtext = preg_replace( '/\r\n?/', "\n", $text );
        $newtext = preg_replace( '/\n\r?/', "\n", $newtext );
        $newtext = preg_replace( '/\n$/', '', $newtext );


        //
        // Explode
        // -----------------------------------------------------
        //   Explode the string into lines on LF. We've already
        //   insured that LF doesn't exist in any quoted text.
        $lines = explode( "\n", $newtext );
        unset( $newtext );


        //
        // Parse
        // -----------------------------------------------------
        //   Explode each line on a tab.
        $rows = array_map(
            function( $line )
            {
                return explode( "\t", $line );
            }, $lines );
        unset( $lines );

        // If there are no rows, the file was empty and there is
        // no table to return.
        //
        // This 'if' checks will stay in the code, but there appears
        // to be no way to trigger it. An empty string '' is caught
        // earlier. A white-space string '   ' is really one row of
        // text in one column.  So, there is no obvious way to
        // hit this condition, but let's be paranoid.
        // @codeCoverageIgnoreStart
        if ( count( $rows ) == 0 )
            return array( );
        // @codeCoverageIgnoreEnd

        // The first row should be the column names. We have no way
        // of knowing if it is or not, so we just have to hope.
        $header   = array_shift( $rows );
        $nColumns = count( $header );

        // An empty file parsed as TSV produces a single column,
        // no rows, and a column with an empty name. Catch this
        // and return a NULL.
        if ( count( $rows ) == 0 && $nColumns == 1 && empty( $header[0] ) )
            return array( );

        // Every row must have the same number of values, and that
        // number must match the header.
        foreach ( $rows as &$row )
        {
            if ( count( $row ) != $nColumns )
                throw new SyntaxException(
                    'TSV table rows must all have the same number of values as the first row.' );
        }


        //
        // Build the table
        // -----------------------------------------------------
        $attributes = array(
            // 'name' unknown
            // 'longName' unknown
            // 'description' unknown
            // 'sourceFileName' unknown
            'sourceMIMEType'   => $this->getMIMEType( ),
            'sourceSyntax'     => $this->getSyntax( ),
            'sourceSchemaName' => $this->getName( )
        );
        $table = new Table( $attributes );


        //
        // Add columns
        // -----------------------------------------------------
        //   Header provides column names.
        //   No column descriptions or data types.
        foreach ( $header as &$field )
            $table->appendColumn( array( 'name' => $field ) );


        // Convert values rows
        // -----------------------------------------------------
        //   So far, every value in every row is a string. But
        //   we'd like to change to the "best" data type for
        //   the value. If it is an integer, make it an integer.
        //   If it is a float, make it a double. If it is a
        //   boolean, make it a boolean. Only fall back to string
        //   types if nothing better will do.
        foreach ( $rows as &$row )
        {
            foreach ( $row as $key => &$value )
            {
                // Ignore any value except a string. But really,
                // they should all be strings so we're just being
                // paranoid.
                // @codeCoverageIgnoreStart
                if ( !is_string( $value ) )
                    continue;
                // @codeCoverageIgnoreEnd

                $lower = strtolower( $value );
                if ( is_numeric( $value ) )
                {
                    // Convert to float or int.
                    $fValue = floatval( $value );
                    $iValue = intval( $value );

                    // If int and float same, then must be an int
                    if ( $fValue == $iValue )
                        $row[$key] = $iValue;
                    else
                        $row[$key] = $fValue;
                }
                else if ( $lower === 'true' )
                    $row[$key] = true;
                else if ( $lower === 'false' )
                    $row[$key] = false;

                // Otherwise leave it as-is.
            }
        }


        // Add rows
        // -----------------------------------------------------
        //   Parsed content provides rows.
        if ( count( $rows ) != 0 )
            $table->appendRows( $rows );
        return array( $table );
    }




    /**
     * @copydoc AbstractFormat::encode
     *
     * #### Encode limitations
     * The TSV format only supports encoding a single
     * SDSC\StructuredData\Table in the format. An exception is thrown
     * if the $objects argument is not an array, is empty, contains
     * more than one object, or it is not a Table.
     */
    public function encode( &$objects, $options = 0 )
    {
        //
        // Validate arguments
        // -----------------------------------------------------
        if ( $objects == NULL )
            return NULL;            // No table to encode

        if ( !is_array( $objects ) )
            throw new \InvalidArgumentException(
                'TSV encode requires an array of objects.' );

        if ( count( $objects ) > 1 )
            throw new \InvalidArgumentException(
                'TSV encode only supports encoding a single object.' );

        $table = &$objects[0];
        if ( !is_a( $table, 'SDSC\StructuredData\Table', false ) )
            throw new \InvalidArgumentException(
                'TSV encode object must be a table.' );

        $nColumns = $table->getNumberOfColumns( );
        if ( $nColumns <= 0 )
            return NULL;            // No data to encode
        $nRows = $table->getNumberOfRows( );
        $text  = '';


        //
        // Encode header
        // -----------------------------------------------------
        //   Generate a single row with comma-separated column
        //   names.
        for ( $column = 0; $column < $nColumns; $column++ )
        {
            if ( $column != 0 )
                $text .= "\t";

            $text .= $table->getColumnName( $column );
        }
        $text .= "\r\n";


        //
        // Encode rows
        // -----------------------------------------------------
        for ( $row = 0; $row < $nRows; $row++ )
        {
            $r = $table->getRowValues( $row );

            for ( $column = 0; $column < $nColumns; $column++ )
            {
                if ( $column != 0 )
                    $text .= "\t";
                $text .= $r[$column];
            }
            $text .= "\r\n";
        }

        return $text;
    }
    // @}
}

/**
 * @file
 * Defines SDSC\StructuredData\Graph to manage a graph with a root node and
 * children that may, in turn, have children.
 */

namespace SDSC\StructuredData;






/**
 * @class Graph
 * Graph manages a named directed or undirected graph of named nodes
 * and named edges connecting nodes together, where each node and each edge
 * contains list of named values and metadata.
 *
 *
 * #### Graph attributes
 * Graphs have an associative array of attributes that
 * provide descriptive metadata for the data content.  Applications may
 * add any number and type of attributes, but this class, and others in
 * this package, recognize a few well-known attributes:
 *
 *  - 'name' (string) is a brief name of the data
 *  - 'longName' (string) is a longer more human-friendly name of the data
 *  - 'description' (string) is a block of text describing the data
 *  - 'sourceFileName' (string) is the name of the source file for the data
 *  - 'sourceSyntax' (string) is the source file base syntax
 *  - 'sourceMIMEType' (string) is the source file mime type
 *  - 'sourceSchemaName' (string) is the name of a source file schema
 *
 * All attributes are optional.
 *
 * The 'name' may be an abbreviation or acronym, while the 'longName' may
 * spell out the abbreviation or acronym.
 *
 * The 'description' may be a block of text containing several unformatted
 * sentences describing the data.
 *
 * When the data originates from a source file, the 'sourceFileName' may
 * be the name of the file. If that file's syntax does not provide a name
 * for the data, the file's name, without extensions, may be used to set
 * the name.
 *
 * In addition to the source file name, the file's MIME type may be set
 * in 'sourceMIMEType' (e.g. 'application/json'), and the equivalent file
 * syntax in 'sourceSyntax' e.g. 'json'). If the source file uses a specific
 * schema, the name of that schema is in 'sourceSchemaName' (e.g.
 * 'json-graph').
 *
 *
 * #### Node and Edge attributes
 * Graphs have zero or more nodes and zero or more edges between them.
 * Each node and edge has an associative array of attributes that provide
 * descriptive metadata for the node or edge. Applications may add any
 * number and type of attributes, but this class, and others in
 * this package, recognize a few well-known attributes:
 *
 *  - 'name' (string) is a brief name of the data
 *  - 'longName' (string) is a longer more human-friendly name of the data
 *  - 'description' (string) is a block of text describing the data
 *
 * All attributes are optional.
 *
 * The 'name' may be an abbreviation or acronym, while the 'longName' may
 * spell out the abbreviation or acronym.  The node 'name' is optional
 * but strongly encouraged.  If abscent, classes that format nodes for a
 * specific output syntax (e.g. CSV or JSON) will create numbered node
 * names (e.g. '1', '2', etc.).
 *
 * The 'description' may be a block of text containing several unformatted
 * sentences describing the data.
 *
 *
 * #### Nodes and edges
 * Unlike a tree, a graph has no starting point - no root node. Instead,
 * a graph is an unprioritized collection of nodes, where each node may
 * connect to zero or more other nodes in the graph by way of an edge.
 *
 * An edge always connects two nodes. Typically those are different
 * nodes, but it is possible for an edge to connect from and to the
 * same node to create a circular self reference.
 *
 *
 * @author  David R. Nadeau / University of California, San Diego
 *
 * @date    2/8/2016
 *
 * @since   0.0.1  Initial development.
 *
 * @version 0.0.1  Initial development.
 *
 * @version 0.0.2  Revised to subclass AbstractData and throw standard
 *   SPL exceptions.
 */
final class Graph
    extends AbstractData
{
//----------------------------------------------------------------------
// Fields
//----------------------------------------------------------------------
    /**
     * @var  array $nodes
     * An array of nodes with numeric keys. Node "IDs" are keys
     * into this array. The order of nodes is irrelevant.  Deletion
     * of a node unsets the entry, which causes gaps in the array
     * key sequence.
     *
     * Validation of a node ID checks if the ID is a valid key for
     * the array.
     *
     * The number of nodes equals count( $nodes ).
     *
     * Each node in the array is an associative array with keys for:
     *      - 'attributes'  - associative array of named attributes
     *      - 'edges'       - array of edge node IDs as keys
     *
     * The 'attributes' key selects an associative array containing
     * named attributes/values. Well-known attributes include:
     *
     *      - 'name'        - short name
     *      - 'longName'    - long name
     *      - 'description' - description
     *
     * The 'edges' key selects an associative array that always
     * exists and is initially empty.  This array is associative
     * where keys are edge IDs for the edges connecting this node to
     * other nodes, and values are always 0 (they are not used).
     */
    private $nodes;

    /**
     * @var  array $nodeNameMap
     * An associative array with node name string keys. An entry
     * exists if a particular name is used by one or more nodes.
     *
     * Each entry is an associative array where array keys are numeric
     * node IDs, and values are always '0' - the value is not used and
     * is merely there to fill an entry. The array keys are what are
     * used to provide a list of node IDs with the same name.
     */
    private $nodeNameMap;

    /**
     * @var  integer $nextNodeID
     * The next available unique non-negative integer node ID.
     * Node IDs start at 0 for an empty graph, then increment each
     * time a node is added. On deletion, the IDs of deleted nodes
     * are *not* reused. Node IDs are monotonicaly increasing.
     */
    private $nextNodeID;



    /**
     * @var  array $edges
     * An array of edges with numeric edge ID keys and associative
     * array values.  The order of edges is irrelevant.  Deletion
     * of a edge unsets the array entry, causing array keys to *not*
     * be consecutive integers.
     *
     * Validation of a edge ID checks if the ID is a valid key for
     * the array.
     *
     * The number of edges equals count( $edges ).
     *
     * Each edge in the array is an associative array with keys for:
     *      - 'attributes'  - associative array of named attributes
     *      - 'node1'       - the first node for the edge
     *      - 'node2'       - the second node for the edge
     *
     * The 'attributes' key selects an associative array containing
     * named attributes/values. Well-known attributes include:
     *
     *      - 'name'        - short name
     *      - 'longName'    - long name
     *      - 'description' - description
     *
     * The 'node1' and 'node2' keys select scalars containing node IDs,
     * and both are always present for every edge. The two nodes may be
     * the same. The order of the nodes matches the order given when the
     * edge was constructed.
     */
    private $edges;

    /**
     * @var  array $edgeNameMap
     * An associative array with edge name string keys. An entry
     * exists if a particular name is used by one or more edges.
     *
     * Each entry is an associative array where array keys are numeric
     * edge IDs, and values are always '0' - the value is not used and
     * is merely there to fill an entry. The array keys are what are
     * used to provide a list of edge IDs with the same name.
     */
    private $edgeNameMap;

    /**
     * @var  integer $nextNodeID
     * The next available unique non-negative integer edge ID.
     * Edge IDs start at 0 for an empty graph, then increment each
     * time a edge is added. On deletion, the IDs of deleted edges
     * are *not* reused. Edge IDs are monotonicaly increasing.
     */
    private $nextEdgeID;




//----------------------------------------------------------------------
// Constants
//----------------------------------------------------------------------
    /**
     * @var array WELL_KNOWN_NODE_ATTRIBUTES
     * An associative array where the keys are the names of well-known
     * node attributes.
     */
    public static $WELL_KNOWN_NODE_ATTRIBUTES = array(
        'name'        => 1,
        'longName'    => 1,
        'description' => 1
    );

    /**
     * @var array WELL_KNOWN_EDGE_ATTRIBUTES
     * An associative array where the keys are the names of well-known
     * edge attributes.
     */
    public static $WELL_KNOWN_EDGE_ATTRIBUTES = array(
        'name'        => 1,
        'longName'    => 1,
        'description' => 1
    );

    private static $ERROR_graph_node_id_invalid =
        'Node ID is out of bounds.';

    private static $ERROR_node_attributes_invalid =
        'Node attributes must be an array or object.';
    private static $ERROR_node_values_invalid =
        'Node values must be an array or object.';
    private static $ERROR_node_attribute_key_invalid =
        'Node attribute keys must be non-empty strings.';
    private static $ERROR_node_attribute_wellknown_key_value_invalid =
        'Node attribute values for well-known keys must be strings.';

    private static $ERROR_graph_edge_id_invalid =
        'Edge ID is out of bounds.';

    private static $ERROR_edge_attributes_invalid =
        'Edge attributes must be an array or object.';
    private static $ERROR_edge_values_invalid =
        'Edge values must be an array or object.';
    private static $ERROR_edge_attribute_key_invalid =
        'Edge attribute keys must be non-empty strings.';
    private static $ERROR_edge_attribute_wellknown_key_value_invalid =
        'Edge attribute values for well-known keys must be strings.';





//----------------------------------------------------------------------
// Constructors & Destructors
//----------------------------------------------------------------------
    /**
     * @name Constructors
     */
    // @{
    /**
     * Constructs an empty graph with no nodes or edges, and the provided
     * list of attributes, if any.
     *
     *
     * @param   array $attributes  an optional associatve array of named
     * attributes associated with the graph.
     *
     * @return  Graph             returns a new empty graph with the
     * provided graph attributes.
     *
     * @throws \InvalidArgumentException  if $attributes is not an array,
     * object, or NULL, or if any of its attributes have invalid keys or
     * values.
     */
    public function __construct( $attributes = NULL )
    {
        parent::__construct( $attributes );

        // Initialize empty node and edge arrays.
        $this->nodes       = array( );
        $this->nodeNameMap = array( );
        $this->nextNodeID  = 0;

        $this->edges       = array( );
        $this->edgeNameMap = array( );
        $this->nextEdgeID  = 0;
    }

    /**
     * Clones the data by doing a deep copy of all attributes and values.
     */
    public function __clone( )
    {
        // For any property that is an object or array, make a
        // deep copy by forcing a serialize, then unserialize.
        foreach ( $this as $key => &$value )
        {
            if ( is_object( $value ) || is_array( $value ) )
                $this->{$key} = unserialize( serialize( $value ) );
        }
    }
    // @}

    /**
     * @name Destructors
     */
    // @{
    /**
     * Destroys the previously constructed graph.
     */
    public function __destruct( )
    {
        parent::__destruct( );
    }
    // @}





//----------------------------------------------------------------------
// Utility methods
//----------------------------------------------------------------------
    /**
     * @name Utility methods
     */
    // @{
    /**
     * Adds the selected node's ID to the name table with the given name.
     *
     * The given node ID is assumed to be valid.
     *
     * @param integer $nodeID  the unique non-negative numeric ID of a node.
     *
     * @param string   $name    a string containing the name name
     * of the node, or an empty string if there is no name name.
     */
    private function _addNodeToNameMap( $nodeID, $name )
    {
        // The $nodeNameMap is an associative array where names are
        // the keys. Entry values are associative arrays where the
        // keys are node IDs, and the values are irrelevant.
        if ( $name === '' || $name === NULL )
            return;

        // If the map has no current entry for the name, add one.
        // Otherwise, add a key for the new node ID. Values are
        // not used and are always 0.
        if ( !isset( $this->nodeNameMap[$name] ) )
            $this->nodeNameMap[$name]          = array( $nodeID => 0 );
        else
            $this->nodeNameMap[$name][$nodeID] = 0;
    }

    /**
     * Removes the selected node's ID from the name table entry with
     * the given name.
     *
     * The given node ID is assumed to be valid.
     *
     * @param integer $nodeID  the unique non-negative numeric ID of a node.
     *
     * @param string   $name    a string containing the name
     * of the node, or an empty string if there is no name.
     */
    private function _deleteNodeFromNameMap( $nodeID, $name )
    {
        // The $nodeNameMap is an associative array where names are
        // the keys. Entry values are associative arrays where the
        // keys are node IDs, and the values are irrelevant.
        if ( $name === '' || $name === NULL )
            return;

        // If the map has no entry for the name, then the name was not
        // in use and we're done.  This should never happen since all
        // nodes with names are added to the name map.
        //
        // Since this should never happen, there is no way to test this.
        // @codeCoverageIgnoreStart
        if ( !isset( $this->nodeNameMap[$name] ) )
            return;                         // Name is not in use
        // @codeCoverageIgnoreEnd

        // If the map entry has no key for the node, then the node was
        // not registered as using this name and we're done.  Again,
        // this should never happen since all entries have nodes.
        //
        // Since this should never happen, there is no way to test this.
        // @codeCoverageIgnoreStart
        if ( !isset( $this->nodeNameMap[$name][$nodeID] ) )
            return;                         // Node isn't registered for name
        // @codeCoverageIgnoreEnd

        // Unset the map entry's key for the node.
        unset( $this->nodeNameMap[$name][$nodeID] );

        // If that makes the map entry empty, unset it.
        if ( empty( $this->nodeNameMap[$name] ) )
            unset( $this->nodeNameMap[$name] );
    }

    /**
     * Validates a nodeID and throws an exception if the ID is out of
     * range.
     *
     * @param integer $nodeID  the unique non-negative numeric ID of a node.
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     */
    private function _validateNodeID( $nodeID )
    {
        // The $nodes array is an associative array where node IDs are
        // the keys. IDs are always non-negative. If an ID is negative
        // or if there is no entry for the ID, then the ID is not valid.
        if ( $nodeID < 0 || !isset( $this->nodes[$nodeID] ) )
            throw new \OutOfBoundsException(
                self::$ERROR_graph_node_id_invalid );
    }





    /**
     * Adds an edge to a node.
     *
     * The given node ID and edge ID are assumed to be valid.
     *
     * @param integer $nodeID  the unique non-negative numeric ID of a node.
     *
     * @param integer $edgeID  the unique non-negative numeric ID of a edge.
     */
    private function _addEdgeToNode( $nodeID, $edgeID )
    {
        // A node's edge list is an associative array where the keys are
        // edge IDs and the values are always 0 (unused).  This should
        // not be possible since we check for this before trying to
        // add the edge to the node.
        // Since this should never happen, there is no way to test this.
        // @codeCoverageIgnoreStart
        if ( isset( $this->nodes[$nodeID]['edges'][$edgeID] ) )
            return;                                 // Already in edge array
        // @codeCoverageIgnoreEnd
        $this->nodes[$nodeID]['edges'][$edgeID] = 0;
    }

    /**
     * Deletes an edge from a node.
     *
     * The given node ID and edge ID are assumed to be valid.
     *
     * @param integer $nodeID  the unique non-negative numeric ID of a node.
     *
     * @param integer $edgeID  the unique non-negative numeric ID of a edge.
     */
    private function _deleteEdgeFromNode( $nodeID, $edgeID )
    {
        // A node's edge list is an associative array where the keys are
        // edge IDs and the values are always 0 (unused).
        unset( $this->nodes[$nodeID]['edges'][$edgeID] );
    }




    /**
     * Adds the selected edge's ID to the name table with the given name.
     *
     * The given edge ID is assumed to be valid.
     *
     * @param integer $edgeID  the unique non-negative numeric ID of a edge.
     *
     * @param string   $name    a string containing the name name
     * of the edge, or an empty string if there is no name name.
     */
    private function _addEdgeToNameMap( $edgeID, $name )
    {
        // The $edgeNameMap is an associative array where names are
        // the keys. Entry values are associative arrays where the
        // keys are edge IDs, and the values are irrelevant.
        if ( $name === '' || $name === NULL )
            return;

        // If the map has no current entry for the name, add one.
        // Otherwise, add a key for the new edge ID. Values are
        // not used and are always 0.
        if ( !isset( $this->edgeNameMap[$name] ) )
            $this->edgeNameMap[$name]          = array( $edgeID => 0 );
        else
            $this->edgeNameMap[$name][$edgeID] = 0;
    }

    /**
     * Removes the selected edge's ID from the name table entry with
     * the given name.
     *
     * The given edge ID is assumed to be valid.
     *
     * @param integer $edgeID  the unique non-negative numeric ID of a edge.
     *
     * @param string   $name    a string containing the name
     * of the edge, or an empty string if there is no name.
     */
    private function _deleteEdgeFromNameMap( $edgeID, $name )
    {
        // The $edgeNameMap is an associative array where names are
        // the keys. Entry values are associative arrays where the
        // keys are edge IDs, and the values are irrelevant.
        if ( $name === '' || $name === NULL )
            return;

        // If the map has no entry for the name, then the name was not
        // in use and we're done.  This should never happen since all
        // nodes with names are added to the name map.
        //
        // Since this should never happen, there is no way to test this.
        // @codeCoverageIgnoreStart
        if ( !isset( $this->edgeNameMap[$name] ) )
            return;                         // Name is not in use
        // @codeCoverageIgnoreEnd

        // If the map entry has no key for the edge, then the edge was
        // not registered as using this name and we're done.  Again,
        // this should never happen since all entries have edge.
        //
        // Since this should never happen, there is no way to test this.
        // @codeCoverageIgnoreStart
        if ( !isset( $this->edgeNameMap[$name][$edgeID] ) )
            return;                         // Node isn't registered for name
        // @codeCoverageIgnoreEnd

        // Unset the map entry's key for the edge.
        unset( $this->edgeNameMap[$name][$edgeID] );

        // If that makes the map entry empty, unset it.
        if ( empty( $this->edgeNameMap[$name] ) )
            unset( $this->edgeNameMap[$name] );
    }

    /**
     * Validates a edgeID and throws an exception if the ID is out of
     * range.
     *
     * @param integer $edgeID  the unique non-negative numeric ID of a edge.
     *
     * @throws \OutOfBoundsException  if $edgeID is out of bounds.
     */
    private function _validateEdgeID( $edgeID )
    {
        // The $edges array is an associative array where edge IDs are
        // the keys. IDs are always non-negative. If an ID is negative
        // or if there is no entry for the ID, then the ID is not valid.
        if ( $edgeID < 0 || !isset( $this->edges[$edgeID] ) )
            throw new \OutOfBoundsException(
                self::$ERROR_graph_edge_id_invalid );
    }
    // @}





//----------------------------------------------------------------------
// Node attributes methods
//----------------------------------------------------------------------
    /**
     * @name Node attributes methods
     */
    // @{
    /**
     * Clears attributes for the selected node, while retaining its
     * values and edges to other nodes, if any.
     *
     * Example:
     * @code
     *   $graph->clearNodeAttributes( $id );
     * @endcode
     *
     * @param  integer $nodeID  the unique non-negative numeric ID of the node.
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     */
    public function clearNodeAttributes( $nodeID )
    {
        // Validate the node ID.
        $this->_validateNodeID( $nodeID );

        // Clear attributes. If there is a node name, remove the node
        // from the name map.
        if ( !isset( $this->nodes[$nodeID]['attributes']['name'] ) )
            $this->nodes[$nodeID]['attributes'] = array( );
        else
        {
            $name = $this->nodes[$nodeID]['attributes']['name'];
            $this->nodes[$nodeID]['attributes'] = array( );
            $this->_deleteNodeFromNameMap( $nodeID, $name );
        }
    }

    /**
     * Returns an array of node IDs for nodes with the selected
     * name, or an empty array if there are no nodes with the name.
     *
     * Example:
     * @code
     *   $ids = $graph->findNodesByName( 'abc' );
     *   foreach ( $ids as $id )
     *   {
     *     print( "Node $id\n" );
     *   }
     * @endcode
     *
     * @return  array  returns an array of node IDs for nodes with
     * the given name, or an empty array if no nodes were found.
     *
     * @throws \InvalidArgumentException  if $name is not a non-empty string.
     */
    public function findNodesByName( $name )
    {
        // Validate.
        if ( !is_string( $name ) || $name === '' )
            throw new \InvalidArgumentException(
                self::$ERROR_node_attribute_key_invalid );

        // The name map is an associative array where the keys are names
        // and the values are arrays. Those arrays are each associative
        // where the keys are node IDs and the values are unused.

        // If the map has no entry for the name, there are no nodes with
        // that name.
        if ( !isset( $this->nodeNameMap[$name] ) )
            return array( );

        // Otherwise return the keys for that name's array. These are
        // node IDs.
        return array_keys( $this->nodeNameMap[$name] );
    }

    /**
     * Returns a copy of the selected attribute for the selected node,
     * or a NULL if the attribute does not exist.
     *
     * Example:
     * @code
     *   $graph->getNodeAttribute( $id, 'name' );
     * @endcode
     *
     * @param  integer $nodeID  the unique non-negative numeric ID of the node.
     *
     * @param  string  $key     the name of an attribute to query
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     *
     * @throws \InvalidArgumentException  if $key is not a non-empty string.
     */
    public function getNodeAttribute( $nodeID, $key )
    {
        // Validate the node ID.
        $this->_validateNodeID( $nodeID );
        if ( !is_string( $key ) || $key === '' )
            throw new \InvalidArgumentException(
                self::$ERROR_node_attribute_key_invalid );

        // Get.
        if ( isset( $this->nodes[$nodeID]['attributes'][$key] ) )
            return $this->nodes[$nodeID]['attributes'][$key];
        return NULL;                        // No such key
    }

    /**
     * Returns a copy of all attributes for the selected node.
     *
     * Example:
     * @code
     *   $graph->getNodeAttributes( $id );
     * @endcode
     *
     * @param  integer $nodeID  the unique non-negative numeric ID of the node.
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     */
    public function getNodeAttributes( $nodeID )
    {
        // Validate the node ID.
        $this->_validateNodeID( $nodeID );

        // Get.
        return $this->nodes[$nodeID]['attributes'];
    }

    /**
     * Returns a "best" node name by checking for, in order, the long name
     * and short name, and returning the first non-empty value
     * found, or the node id if all of those are empty.
     *
     * Example:
     * @code
     *   $bestName = $data->getNodeBestName( $id );
     * @endcode
     *
     * This method is a convenience function that is the equivalent of
     * checking each of the long name and name attributes in order.
     *
     * @param  integer $nodeID  the unique non-negative numeric ID of the node.
     *
     * @return  the best name
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     */
    public function getNodeBestName( $nodeID )
    {
        $v = $this->getNodeAttribute( $nodeID, 'longName' );
        if ( $v !== '' && $v !== NULL )
            return strval( $v );
        $v = $this->getNodeAttribute( $nodeID, 'name' );
        if ( $v !== '' && $v !== NULL )
            return strval( $v );
        return strval( $nodeID );
    }

    /**
     * Returns the description of the selected node, or an empty string if it
     * has no description.
     *
     * Example:
     * @code
     *   $description = $tree->getNodeDescription( $id );
     * @endcode
     *
     * @param  integer $nodeID  the unique non-negative numeric ID of the node.
     *
     * @return string  the description for the selected node, or an empty
     * string if the node has no description.
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     */
    public function getNodeDescription( $nodeID )
    {
        $v = $this->getNodeAttribute( $nodeID, 'description' );
        if ( $v !== '' && $v !== NULL )
            return strval( $v );
        return '';
    }

    /**
     * Returns the long name of the selected node, or an empty string if it
     * has no long name.
     *
     * Example:
     * @code
     *   $longName = $tree->getNodeLongName( $id );
     * @endcode
     *
     * @param  integer $nodeID  the unique non-negative numeric ID of the node.
     *
     * @return string  the long name for the selected node, or an empty
     * string if the node has no long name.
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     */
    public function getNodeLongName( $nodeID )
    {
        $v = $this->getNodeAttribute( $nodeID, 'longName' );
        if ( $v !== '' && $v !== NULL )
            return strval( $v );
        return '';
    }

    /**
     * Returns the name of the selected node, or an empty string if it
     * has no name.
     *
     * Example:
     * @code
     *   $name = $tree->getNodeName( $id );
     * @endcode
     *
     * @param  integer $nodeID  the unique non-negative numeric ID of the node.
     *
     * @return string  the name for the selected node, or an empty string if
     * the node has no name.
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     */
    public function getNodeName( $nodeID )
    {
        $v = $this->getNodeAttribute( $nodeID, 'name' );
        if ( $v !== '' && $v !== NULL )
            return strval( $v );
        return '';
    }

    /**
     * Returns an array of keywords found in the node's attributes,
     * including the name, long name, description, and other attributes.
     *
     * Such a keyword list is useful when building a search index to
     * find this data object. The returns keywords array is in
     * lower case, with duplicate words removed, and the array sorted
     * in a natural sort order.
     *
     * The keyword list is formed by extracting all space or punctuation
     * delimited words found in all attribute keys and values. This
     * includes the name, long name, and description attributes, and
     * any others added by the application. Well known attribute
     * names, such as 'name', 'longName', etc., are not included, but
     * application-specific attribute names are included.
     *
     * Numbers and punctuation are ignored. Array and object attribute
     * values are converted to text and then scanned for words.
     *
     * @param  integer $nodeID  the unique non-negative numeric ID of the node.
     *
     * @return array  returns an array of keywords.
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     */
    public function getNodeKeywords( $nodeID )
    {
        // Add all node attribute keys and values for one node.
        $text = '';
        foreach ( $this->nodes[$nodeID]['attributes'] as $key => &$value )
        {
            // Add the key. Skip well-known key names.  Intelligently
            // convert to text.
            if ( !isset( self::$WELL_KNOWN_NODE_ATTRIBUTES[$key] ) )
                $text .= ' ' . $this->valueToText( $key );

            // Add the value.  Intelligently convert to text.
            $text .= ' ' . $this->valueToText( $value );
        }

        // Clean the text of numbers and punctuation, and return
        // an array of keywords.
        return $this->textToKeywords( $text );
    }

    /**
     * Returns an array of keywords found in all node attributes,
     * including the names, long names, descriptions, and other attributes.
     *
     * Such a keyword list is useful when building a search index to
     * find this data object. The returns keywords array is in
     * lower case, with duplicate words removed, and the array sorted
     * in a natural sort order.
     *
     * The keyword list is formed by extracting all space or punctuation
     * delimited words found in all attribute keys and values. This
     * includes the name, long name, and description attributes, and
     * any others added by the application. Well known attribute
     * names, such as 'name', 'longName', etc., are not included, but
     * application-specific attribute names are included.
     *
     * Numbers and punctuation are ignored. Array and object attribute
     * values are converted to text and then scanned for words.
     *
     * @return array  returns an array of keywords.
     */
    public function getAllNodeKeywords( )
    {
        // Add all node attribute keys and values for all nodes.
        $text = '';
        foreach ( $this->nodes as &$node )
        {
            foreach ( $node['attributes'] as $key => &$value )
            {
                // Add the key. Skip well-known key names.  Intelligently
                // convert to text.
                if ( !isset( self::$WELL_KNOWN_NODE_ATTRIBUTES[$key] ) )
                    $text .= ' ' . $this->valueToText( $key );

                // Add the value.  Intelligently convert to text.
                $text .= ' ' . $this->valueToText( $value );
            }
        }

        // Clean the text of numbers and punctuation, and return
        // an array of keywords.
        return $this->textToKeywords( $text );
    }

    /**
     * Merges the given named attribute with the selected node's
     * existing attributes.
     *
     * New attributes overwrite existing attributes with the same name.
     *
     * Example:
     * @code
     *   $table->setNodeAttribute( $id, 'name', 'Total' );
     * @endcode
     *
     * @param integer $nodeID  the non-negative numeric index of the node.
     *
     * @param string  $key  the key of a node attribute.
     *
     * @param mixed   $value  the value of a node attribute.
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     *
     * @throws \InvalidArgumentException  if $key is not a string or is empty,
     * or if $value is not a string when $key is one of the well-known
     * attributes.
     */
    public function setNodeAttribute( $nodeID, $key, $value )
    {
        // Validate. Insure the key is a string and the value for
        // well-known attributes is a string.
        $this->_validateNodeID( $nodeID );
        if ( !is_string( $key ) || $key === '' )
            throw new \InvalidArgumentException(
                self::$ERROR_node_attribute_key_invalid );

        if ( isset( self::$WELL_KNOWN_NODE_ATTRIBUTES[$key] ) &&
            !is_string( $value ) )
            throw new \InvalidArgumentException(
                self::$ERROR_node_attribute_wellknown_key_value_invalid );

        // Set. If the name changes, update the name map.
        if ( (string)$key == 'name' )
        {
            $oldName = $this->nodes[$nodeID]['attributes']['name'];
            $this->_deleteNodeFromNameMap( $nodeID, $oldName );
            $this->nodes[$nodeID]['attributes']['name'] = $value;
            $this->_addNodeToNameMap( $nodeID, 'name' );
        }
        else
            $this->nodes[$nodeID]['attributes'][$key] = $value;
    }

    /**
     * Merges the given associative array of named attributes with the
     * selected node's existing attributes, if any.
     *
     * New attributes overwrite existing attributes with the same name.
     *
     * The node's attributes array may contain additional application-
     * or file format-specific attributes.
     *
     * Example:
     * @code
     *   $attributes = array( 'name' => 'Total' );
     *   $table->setNodeAttributes( $id, $attributes );
     * @endcode
     *
     * @param  integer $nodeID  the unique non-negative numeric ID of the node.
     *
     * @param   array $attributes  an associatve array of named
     * attributes associated with the node.
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     *
     * @throws \InvalidArgumentException  if $attributes is not an array,
     * object, or NULL, or if any of its attributes have invalid keys or
     * values.
     */
    public function setNodeAttributes( $nodeID, $attributes )
    {
        // Validate
        $this->_validateNodeID( $nodeID );
        if ( $attributes == NULL )
            return;                     // Request to set with nothing
        if ( !is_array( $attributes ) && !is_object( $attributes ) )
            throw new \InvalidArgumentException(
                self::$ERROR_node_attributes_invalid );

        // Convert object argument to an array, if needed.
        $a = (array)$attributes;
        if ( empty( $a ) )
            return;

        // Insure keys are all strings and all well-known key values
        // are strings.
        foreach ( $a as $key => $value )
        {
            if ( !is_string( $key ) || $key === '' )
                throw new \InvalidArgumentException(
                    self::$ERROR_node_attribute_key_invalid );

            if ( isset( self::$WELL_KNOWN_NODE_ATTRIBUTES[$key] ) &&
                !is_string( $value ) )
                throw new \InvalidArgumentException(
                    self::$ERROR_node_attribute_wellknown_key_value_invalid );
        }

        // Get the old name, if any.
        if ( isset( $this->nodes[$nodeID]['attributes']['name'] ) )
            $oldName = $this->nodes[$nodeID]['attributes']['name'];
        else
            $oldName = NULL;

        // Set attributes.
        $this->nodes[$nodeID]['attributes'] =
            array_merge( $this->nodes[$nodeID]['attributes'], $a );

        // If the name changed, update the node-to-ID map.
        $newName = $this->nodes[$nodeID]['attributes']['name'];
        if ( $oldName != $newName )
        {
            $this->_deleteNodeFromNameMap( $nodeID, $oldName );
            $this->_addNodeToNameMap( $nodeID, $newName );
        }
    }
    // @}





//----------------------------------------------------------------------
// Edge attributes methods
//----------------------------------------------------------------------
    /**
     * @name Edge attributes methods
     */
    // @{
    /**
     * Clears attributes for the selected edge, while retaining its
     * values and nodes on either end of the edge.
     *
     * Example:
     * @code
     *   $graph->clearEdgeAttributes( $id );
     * @endcode
     *
     * @param  integer $edgeID  the unique non-negative numeric ID of the edge.
     *
     * @throws \OutOfBoundsException  if $edgeID is out of bounds.
     */
    public function clearEdgeAttributes( $edgeID )
    {
        $this->_validateEdgeID( $edgeID );

        // Clear attributes. If there is an edge name, remove the edge
        // from the name map.
        if ( !isset( $this->edges[$edgeID]['attributes']['name'] ) )
            $this->edges[$edgeID]['attributes'] = array( );
        else
        {
            $name = $this->edges[$edgeID]['attributes']['name'];
            $this->edges[$edgeID]['attributes'] = array( );
            $this->_deleteEdgeFromNameMap( $edgeID, $name );
        }
    }

    /**
     * Returns an array of edge IDs for edges with the selected
     * name, or an empty array if there are no edges with the name.
     *
     * Example:
     * @code
     *   $ids = $graph->findEdgesByName( 'abc' );
     *   foreach ( $ids as $id )
     *   {
     *     print( "Edge $id\n" );
     *   }
     * @endcode
     *
     * @return  array  returns an array of edge IDs for edges with
     * the given name, or an empty array if no edges were found.
     *
     * @throws \InvalidArgumentException  if $name is not a non-empty string.
     */
    public function findEdgesByName( $name )
    {
        // Validate.
        if ( !is_string( $name ) || $name === '' )
            throw new \InvalidArgumentException(
                self::$ERROR_edge_attribute_key_invalid );

        // The name map is an associative array where the keys are names
        // and the values are arrays. Those arrays are each associative
        // where the keys are edge IDs and the values are unused.

        // If the map has no entry for the name, there are no edges with
        // that name.
        if ( !isset( $this->edgeNameMap[$name] ) )
            return array( );

        // Otherwise return the keys for that name's array. These are
        // edge IDs.
        return array_keys( $this->edgeNameMap[$name] );
    }

    /**
     * Returns a copy of the selected attribute for the selected edge,
     * or a NULL if the attribute does not exist.
     *
     * Example:
     * @code
     *   $graph->getEdgeAttribute( $id, 'name' );
     * @endcode
     *
     * @param  integer $edgeID  the unique non-negative numeric ID of the edge.
     *
     * @param  string  $key     the name of an attribute to query
     *
     * @throws \OutOfBoundsException  if $edgeID is out of bounds.
     *
     * @throws \InvalidArgumentException  if $key is not a non-empty string.
     */
    public function getEdgeAttribute( $edgeID, $key )
    {
        // Validate the node ID.
        $this->_validateEdgeID( $edgeID );
        if ( !is_string( $key ) || $key === '' )
            throw new \InvalidArgumentException(
                self::$ERROR_edge_attribute_key_invalid );

        // Get.
        if ( isset( $this->edges[$edgeID]['attributes'][$key] ) )
            return $this->edges[$edgeID]['attributes'][$key];
        return NULL;                        // No such key
    }

    /**
     * Returns a copy of all attributes for the selected edge.
     *
     * Example:
     * @code
     *   $graph->getEdgeAttributes( $id );
     * @endcode
     *
     * @param  integer $edgeID  the unique non-negative numeric ID of the edge.
     *
     * @throws \OutOfBoundsException  if $edgeID is out of bounds.
     */
    public function getEdgeAttributes( $edgeID )
    {
        // Validate the edge ID.
        $this->_validateEdgeID( $edgeID );

        // Get.
        return $this->edges[$edgeID]['attributes'];
    }

    /**
     * Returns a "best" edge name by checking for, in order, the long name
     * and short name, and returning the first non-empty value
     * found, or the edge id if all of those are empty.
     *
     * Example:
     * @code
     *   $bestName = $data->getEdgeBestName( $id );
     * @endcode
     *
     * This method is a convenience function that is the equivalent of
     * checking each of the long name and name attributes in order.
     *
     * @param  integer $edgeID  the unique non-negative numeric ID of the edge.
     *
     * @return  the best name
     *
     * @throws \OutOfBoundsException  if $edgeID is out of bounds.
     */
    public function getEdgeBestName( $edgeID )
    {
        $v = $this->getEdgeAttribute( $edgeID, 'longName' );
        if ( $v !== '' && $v !== NULL )
            return strval( $v );
        $v = $this->getEdgeAttribute( $edgeID, 'name' );
        if ( $v !== '' && $v !== NULL )
            return strval( $v );
        return strval( $edgeID );
    }

    /**
     * Returns the description of the selected edge, or an empty string if it
     * has no description.
     *
     * Example:
     * @code
     *   $description = $graph->getEdgeDescription( $id );
     * @endcode
     *
     * @param  integer $edgeID  the unique non-negative numeric ID of the edge.
     *
     * @return string  the description for the selected edge, or an empty
     * string if the edge has no description.
     *
     * @throws \OutOfBoundsException  if $edgeID is out of bounds.
     */
    public function getEdgeDescription( $edgeID )
    {
        $v = $this->getEdgeAttribute( $edgeID, 'description' );
        if ( $v !== '' && $v !== NULL )
            return strval( $v );
        return '';
    }

    /**
     * Returns the long name of the selected edge, or an empty string if it
     * has no long name.
     *
     * Example:
     * @code
     *   $longName = $graph->getEdgeLongName( $id );
     * @endcode
     *
     * @param  integer $edgeID  the unique non-negative numeric ID of the edge.
     *
     * @return string  the long name for the selected edge, or an empty
     * string if the edge has no long name.
     *
     * @throws \OutOfBoundsException  if $edgeID is out of bounds.
     */
    public function getEdgeLongName( $edgeID )
    {
        $v = $this->getEdgeAttribute( $edgeID, 'longName' );
        if ( $v !== '' && $v !== NULL )
            return strval( $v );
        return '';
    }

    /**
     * Returns the name of the selected edge, or an empty string if it
     * has no name.
     *
     * Example:
     * @code
     *   $name = $graph->getEdgeName( $id );
     * @endcode
     *
     * @param  integer $edgeID  the unique non-negative numeric ID of the edge.
     *
     * @return string  the name for the selected edge, or an empty string if
     * the edge has no name.
     *
     * @throws \OutOfBoundsException  if $edgeID is out of bounds.
     */
    public function getEdgeName( $edgeID )
    {
        $v = $this->getEdgeAttribute( $edgeID, 'name' );
        if ( $v !== '' && $v !== NULL )
            return strval( $v );
        return '';
    }

    /**
     * Returns an array of keywords found in the edge's attributes,
     * including the name, long name, description, and other attributes.
     *
     * Such a keyword list is useful when building a search index to
     * find this data object. The returns keywords array is in
     * lower case, with duplicate words removed, and the array sorted
     * in a natural sort order.
     *
     * The keyword list is formed by extracting all space or punctuation
     * delimited words found in all attribute keys and values. This
     * includes the name, long name, and description attributes, and
     * any others added by the application. Well known attribute
     * names, such as 'name', 'longName', etc., are not included, but
     * application-specific attribute names are included.
     *
     * Numbers and punctuation are ignored. Array and object attribute
     * values are converted to text and then scanned for words.
     *
     * @param  integer $edgeID  the unique non-negative numeric ID of the edge.
     *
     * @return array  returns an array of keywords.
     *
     * @throws \OutOfBoundsException  if $edgeID is out of bounds.
     */
    public function getEdgeKeywords( $edgeID )
    {
        // Add all edge attribute keys and values for one edge.
        $text = '';
        foreach ( $this->edges[$edgeID]['attributes'] as $key => &$value )
        {
            // Add the key. Skip well-known key names.  Intelligently
            // convert to text.
            if ( !isset( self::$WELL_KNOWN_EDGE_ATTRIBUTES[$key] ) )
                $text .= ' ' . $this->valueToText( $key );

            // Add the value.  Intelligently convert to text.
            $text .= ' ' . $this->valueToText( $value );
        }

        // Clean the text of numbers and punctuation, and return
        // an array of keywords.
        return $this->textToKeywords( $text );
    }

    /**
     * Returns an array of keywords found in all edge attributes,
     * including the names, long names, descriptions, and other attributes.
     *
     * Such a keyword list is useful when building a search index to
     * find this data object. The returns keywords array is in
     * lower case, with duplicate words removed, and the array sorted
     * in a natural sort order.
     *
     * The keyword list is formed by extracting all space or punctuation
     * delimited words found in all attribute keys and values. This
     * includes the name, long name, and description attributes, and
     * any others added by the application. Well known attribute
     * names, such as 'name', 'longName', etc., are not included, but
     * application-specific attribute names are included.
     *
     * Numbers and punctuation are ignored. Array and object attribute
     * values are converted to text and then scanned for words.
     *
     * @return array  returns an array of keywords.
     */
    public function getAllEdgeKeywords( )
    {
        // Add all edge attribute keys and values for all edges.
        $text = '';
        foreach ( $this->edges as &$edge )
        {
            foreach ( $edge['attributes'] as $key => &$value )
            {
                // Add the key. Skip well-known key names.  Intelligently
                // convert to text.
                if ( !isset( self::$WELL_KNOWN_EDGE_ATTRIBUTES[$key] ) )
                    $text .= ' ' . $this->valueToText( $key );

                // Add the value.  Intelligently convert to text.
                $text .= ' ' . $this->valueToText( $value );
            }
        }

        // Clean the text of numbers and punctuation, and return
        // an array of keywords.
        return $this->textToKeywords( $text );
    }

    /**
     * Merges the given named attribute with the selected edge's
     * existing attributes.
     *
     * New attributes overwrite existing attributes with the same name.
     *
     * Example:
     * @code
     *   $table->setEdgeAttribute( $id, 'name', 'Total' );
     * @endcode
     *
     * @param integer $edgeID  the non-negative numeric index of the edge.
     *
     * @param string  $key  the key of a edge attribute.
     *
     * @param mixed   $value  the value of a edge attribute.
     *
     * @throws \OutOfBoundsException  if $edgeID is out of bounds.
     *
     * @throws \InvalidArgumentException  if $key is not a string or is empty,
     * or if $value is not a string when $key is one of the well-known
     * attributes.
     */
    public function setEdgeAttribute( $edgeID, $key, $value )
    {
        // Validate. Insure the key is a string and the value for
        // well-known attributes is a string.
        $this->_validateEdgeID( $edgeID );
        if ( !is_string( $key ) || $key === '' )
            throw new \InvalidArgumentException(
                self::$ERROR_edge_attribute_key_invalid );

        if ( isset( self::$WELL_KNOWN_EDGE_ATTRIBUTES[$key] ) &&
            !is_string( $value ) )
            throw new \InvalidArgumentException(
                self::$ERROR_edge_attribute_wellknown_key_value_invalid );

        // Set. If the name changes, update the name map.
        if ( (string)$key == 'name' )
        {
            $oldName = $this->edges[$edgeID]['attributes']['name'];
            $this->_deleteEdgeFromNameMap( $edgeID, $oldName );
            $this->edges[$edgeID]['attributes']['name'] = $value;
            $this->_addEdgeToNameMap( $edgeID, 'name' );
        }
        else
            $this->edges[$edgeID]['attributes'][$key] = $value;
    }

    /**
     * Merges the given associative array of named attributes with the
     * selected edge's existing attributes, if any.
     *
     * New attributes overwrite existing attributes with the same name.
     *
     * The edge's attributes array may contain additional application-
     * or file format-specific attributes.
     *
     * Example:
     * @code
     *   $attributes = array( 'name' => 'Total' );
     *   $table->setEdgeAttributes( $id, $attributes );
     * @endcode
     *
     * @param  integer $edgeID  the unique non-negative numeric ID of the edge.
     *
     * @param   array $attributes  an associatve array of named
     * attributes associated with the edge.
     *
     * @throws \OutOfBoundsException  if $edgeID is out of bounds.
     *
     * @throws \InvalidArgumentException  if $attributes is not an array,
     * object, or NULL, or if any of its attributes have invalid keys or
     * values.
     */
    public function setEdgeAttributes( $edgeID, $attributes )
    {
        // Validate
        $this->_validateEdgeID( $edgeID );
        if ( $attributes == NULL )
            return;                     // Request to set with nothing
        if ( !is_array( $attributes ) && !is_object( $attributes ) )
            throw new \InvalidArgumentException(
                self::$ERROR_edge_attributes_invalid );

        // Convert object argument to an array, if needed.
        $a = (array)$attributes;
        if ( empty( $a ) )
            return;

        // Insure keys are all strings and all well-known key values
        // are strings.
        foreach ( $a as $key => $value )
        {
            if ( !is_string( $key ) || $key === '' )
                throw new \InvalidArgumentException(
                    self::$ERROR_edge_attribute_key_invalid );

            if ( isset( self::$WELL_KNOWN_NODE_ATTRIBUTES[$key] ) &&
                !is_string( $value ) )
                throw new \InvalidArgumentException(
                    self::$ERROR_edge_attribute_wellknown_key_value_invalid );
        }

        // Get the old name, if any.
        if ( isset( $this->edges[$edgeID]['attributes']['name'] ) )
            $oldName = $this->edges[$edgeID]['attributes']['name'];
        else
            $oldName = NULL;

        // Set attributes.
        $this->edges[$edgeID]['attributes'] =
            array_merge( $this->edges[$edgeID]['attributes'], $a );

        // If the name changed, update the edge-to-ID map.
        $newName = $this->edges[$edgeID]['attributes']['name'];
        if ( $oldName != $newName )
        {
            $this->_deleteEdgeFromNameMap( $edgeID, $oldName );
            $this->_addEdgeToNameMap( $edgeID, $newName );
        }
    }
    // @}





//----------------------------------------------------------------------
// Node operations
//----------------------------------------------------------------------
    /**
     * @name Node operations
     */
    // @{
    /**
     * Adds a new node initialized with the given attributes and values.
     *
     * Example:
     * @code
     *   $nodeID = $graph->addNode( $attributes );
     * @endcode
     *
     * @param array    $attributes    an associative array of named attributes
     * for the node, or an empty array or NULL if there are no attributes.
     *
     * @return integer               the unique non-negative numeric ID of
     * the new node.
     *
     * @throws \InvalidArgumentException  if $attributes is not an array,
     * object, or NULL.
     */
    public function addNode( $attributes = NULL )
    {
        // Validate the attributes and values arrays.
        if ( !is_array( $attributes ) && !is_object( $attributes ) &&
            $attributes != NULL )
            throw new \InvalidArgumentException(
                self::$ERROR_node_attributes_invalid );

        // Create a node entry with attributes, values, and no edges.
        // A node entry is an associative array containing
        // a few specific internal attributes (values, edges)
        // and an arbitrary list of well-known and application-specific
        // attributes.
        $node = array( );
        $node['edges']  = array( );
        if ( empty( $attributes ) )
            $node['attributes'] = array( );
        else
            $node['attributes'] = (array)$attributes;

        // Use the next available node ID and add the node to the
        // nodes array using that node ID.
        $nodeID = $this->nextNodeID;
        ++$this->nextNodeID;
        $this->nodes[$nodeID] = $node;

        // Add to the name-to-ID table.
        if ( isset( $node['attributes']['name'] ) )
            $this->_addNodeToNameMap( $nodeID, $node['attributes']['name'] );

        return $nodeID;
    }

    /**
     * Clears the entire graph, removing all nodes, edges, and
     * graph attributes, leaving an entirely empty graph.
     *
     * This method is equivalent to clearing all graph attributes, then
     * deleting all nodes:
     * @code
     *   $graph->clearAttributes( );
     *   $graph->deleteNodes( 0, $graph->getNumberOfNodes( ) );
     * @endcode
     *
     * Example:
     * @code
     *   $graph->clear( );
     * @endcode
     *
     * @see clearAttributes( ) to clear graph attributes while retaining nodes.
     *
     * @see deleteNodes( ) to delete nodes in the graph, while
     *   retaining graph attributes.
     */
    public function clear( )
    {
        // Initialize all arrays to be empty.
        $this->clearAttributes( );

        $this->nodes       = array( );  // Delete nodes
        $this->nodeNameMap = array( );
        $this->nextNodeID  = 0;

        $this->edges       = array( );  // Delete edges
        $this->edgeNameMap = array( );
        $this->nextEdgeID  = 0;
    }

    /**
     * Deletes a selected node and all edges between it and other nodes.
     *
     * Example:
     * @code
     *   $graph->deleteNode( $nodeID );
     * @endcode
     *
     * @param integer $nodeID  the unique non-negative numeric ID of a node.
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     */
    public function deleteNode( $nodeID )
    {
        // Validate the node ID.
        $this->_validateNodeID( $nodeID );

        // Copy the node's edges, if any.
        $name = NULL;
        if ( isset( $this->nodes[$nodeID]['attributes']['name'] ) )
            $name  = $this->nodes[$nodeID]['attributes']['name'];
        $edges = array( );
        if ( isset( $this->nodes[$nodeID]['edges'] ) )
            $edges = $this->nodes[$nodeID]['edges'];

        // Delete edges to and from the node. Edges cannot exist if
        // one of the nodes for the edge doesn't exist.
        foreach ( $edges as $key => $edgeID )
            $this->deleteEdge( $edgeID );

        // Delete the node.
        unset( $this->nodes[$nodeID] );
        $this->_deleteNodeFromNameMap( $nodeID, $name );
    }

    /**
     * Returns an array of nodeIDs for all nodes in the graph.
     *
     * @return  array  an array of unique non-negative numeric IDs for all
     * nodes in the graph.
     */
    public function getAllNodes( )
    {
        // The $nodes array is associative where the keys are node IDs.
        // Return an array of those keys.
        return array_keys( $this->nodes );
    }

    /**
     * Returns an array of the edge IDs for all edges to or from the
     * selected node, or an empty array if there are no edges for
     * the node.
     *
     * Example:
     * @code
     *   $edges = $graph->getNodeEdges( $nodeID );
     *   foreach ( $edges as $edgeID )
     *   {
     *     $edgeAttributes = $graph->getEdgeAttributes( $edgeID );
     *   }
     * @endcode
     *
     * @param integer $nodeID  the unique non-negative numeric ID of a node.
     *
     * @return  array   an array of unique non-negative numeric IDs for
     * all edges for the node.
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     */
    public function getNodeEdges( $nodeID )
    {
        // Validate node ID.
        $this->_validateNodeID( $nodeID );

        // The 'edges' attribute of a node is an associative array with
        // edge IDs as keys and values that are 0 (not used).  Return
        // that array's keys.
        return array_keys( $this->nodes[$nodeID]['edges'] );
    }

    /**
     * Returns the total number of nodes in the graph.
     *
     * Example:
     * @code
     *   $number = $graph->getNumberOfNodes( );
     * @endcode
     *
     * @return integer  returns the number of nodes in the graph.
     */
    public function getNumberOfNodes( )
    {
        return count( $this->nodes );
    }
    // @}





//----------------------------------------------------------------------
// Edge operations
//----------------------------------------------------------------------
    /**
     * @name Edge operations
     */
    // @{
    /**
     * Adds a new edge initialized with the given attributes and values
     * and connecting together the two indicated nodes.
     *
     * While typically an edge connects two different nodes, an edge
     * may connect from and to the same node to create a circular
     * self-referencing connection in the graph.
     *
     * Edges may have a direction by setting the 'direction' attribute
     * for the edge. Typical attribute values are:
     *
     * - 'nondirectional' for an edge that has no direction
     * - 'directional' for an edge directed from the first node to the second
     * - 'bidirectional' for an edge directed both from the first node to
     * the second an from the second node back to the first
     *
     * Example:
     * @code
     *   $edgeID = $graph->addEdge( $nodeID1, $nodeID2, $attributes );
     * @endcode
     *
     * @param  integer $nodeID1  the unique non-negative numeric ID of the
     * first node at one end of the edge.
     *
     * @param  integer $nodeID2  the unique non-negative numeric ID of the
     * second node at one end of the edge.
     *
     * @param array    $attributes    an associative array of named attributes
     * for the edge, or an empty array or NULL if there are no attributes.
     *
     * @return integer               the unique non-negative numeric ID of
     * the new edge.
     *
     * @throws \InvalidArgumentException  if $attributes is not an array,
     * object, or NULL.
     */
    public function addEdge( $nodeID1, $nodeID2, $attributes = NULL )
    {
        // Validate node IDs.
        $this->_validateNodeId( $nodeID1 );
        if ( $nodeID2 != $nodeID1 )
            $this->_validateNodeId( $nodeID2 );

        // Validate attributes and values arrays.
        if ( !is_array( $attributes ) && !is_object( $attributes ) &&
            $attributes != NULL )
            throw new \InvalidArgumentException(
                self::$ERROR_edge_attributes_invalid );

        // Create a edge entry with attributes, values, and end nodes.
        // A edge entry is an associative array containing
        // a few specific internal attributes (values, node1, node2)
        // and an arbitrary list of well-known and application-specific
        // attributes.
        $edge = array( );
        $edge['node1']  = $nodeID1;
        $edge['node2']  = $nodeID2;
        if ( empty( $attributes ) )
            $edge['attributes'] = array( );
        else
            $edge['attributes'] = (array)$attributes;

        // Use the next available edge ID and add the edge to the
        // edges array using that edge ID.
        $edgeID = $this->nextEdgeID;
        ++$this->nextEdgeID;
        $this->edges[$edgeID] = $edge;

        // Add to the name-to-ID table.
        if ( isset( $edge['attributes']['name'] ) )
            $this->_addEdgeToNameMap( $edgeID, $edge['attributes']['name'] );

        // Add edge to both nodes.
        $this->_addEdgeToNode( $nodeID1, $edgeID );
        if ( $nodeID2 != $nodeID1 )
            $this->_addEdgeToNode( $nodeID2, $edgeID );

        return $edgeID;
    }

    /**
     * Deletes a selected edge.
     *
     * Example:
     * @code
     *   $graph->deleteEdge( $edgeID );
     * @endcode
     *
     * @param integer $edgeID  the unique non-negative numeric ID of a edge.
     *
     * @throws \OutOfBoundsException  if $edgeID is out of bounds.
     */
    public function deleteEdge( $edgeID )
    {
        // Validate edge ID.
        $this->_validateEdgeID( $edgeID );

        // Note the nodes on either end of the edge.
        $nodeID1 = $this->edges[$edgeID]['node1'];
        $nodeID2 = $this->edges[$edgeID]['node2'];

        // Delete the edge from tables.
        $name = NULL;
        if ( isset( $this->edges[$edgeID]['attributes']['name'] ) )
            $name = $this->edges[$edgeID]['attributes']['name'];
        unset( $this->edges[$edgeID] );
        $this->_deleteEdgeFromNameMap( $edgeID, $name );

        // Delete edges from the node's edge lists.
        $this->_deleteEdgeFromNode( $nodeID1, $edgeID );
        if ( $nodeID2 != $nodeID1 )
            $this->_deleteEdgeFromNode( $nodeID2, $edgeID );
    }

    /**
     * Returns an array of edgeIDs for all edges in the graph.
     *
     * @return  array  an array of unique non-negative numeric IDs for all
     * edges in the graph.
     */
    public function getAllEdges( )
    {
        // The $edges array is associative where the keys are edge IDs.
        // Return an array of those keys.
        return array_keys( $this->edges );
    }

    /**
     * Returns an array of the two node IDs for either end of the
     * selected edge.
     *
     * Example:
     * @code
     *   $nodes = $graph->getEdgeNodes( $edgeID );
     *   $node1 = $nodes[0];
     *   $node2 = $nodes[1];
     * @endcode
     *
     * @param integer $edgeID  the unique non-negative numeric ID of a edge.
     *
     * @return  array  an array of unique non-negative numeric IDs for the
     * two nodes on either end of the edge.
     *
     * @throws \OutOfBoundsException  if $edgeID is out of bounds.
     */
    public function getEdgeNodes( $edgeID )
    {
        // Validate edge ID.
        $this->_validateEdgeID( $edgeID );

        return array(
            $this->edges[$edgeID]['node1'],
            $this->edges[$edgeID]['node2']
        );
    }

    /**
     * Returns the total number of edges in the graph.
     *
     * Example:
     * @code
     *   $number = $graph->getNumberOfEdges( );
     * @endcode
     *
     * @return integer  returns the number of edges in the graph.
     */
    public function getNumberOfEdges( )
    {
        return count( $this->edges );
    }
    // @}
}

/**
 * @file
 * Define the StructuredData API version and copyright, then include each
 * of the class definitions in dependency order, with base classes
 * first, followed by those that depend upon them.
 */
namespace SDSC\StructuredData;





// For unit testing, code coverage is irrelevant for these require calls.
// @codeCoverageIgnoreStart

// Data classes







// Format classes
















// @codeCoverageIgnoreEnd



/**
 * Defines the StructuredData class to hold package globals that define the
 * name, version, author, and copyright message for the API.
 *
 *
 * @author	David R. Nadeau / University of California at San Diego
 *
 * @date    9/10/2018
 */
final class StructuredData
{
    /**
     * The name of the API.
     */
    const Name    = 'SDSC Structured Data API';

    /**
     * The current version of the API.
     */
    const Version = 'Version 1.0.1, September 24, 2018';

    /**
     * The author(s) of the API.
     */
    const Author  = 'David R. Nadeau / San Diego Supercomputer Center (SDSC)';

    /**
     * A copyright message for the API.
     */
    const Copyright = 'Copyright (c) Regents of the University of California';

    /**
     * A license message for the API.
     */
    const License = 'See https://opensource.org/licenses/BSD-2-Clause';
}

/**
 * @file
 * Defines SDSC\StructuredData\Table to manage a table containing values
 * arranged in rows and columns, along with metadata describing the
 * table and its columns.
 */

namespace SDSC\StructuredData;






/**
 * @class Table
 * Table manages a table containing values arranged in rows and columns,
 * along with metadata describing the table and its columns.
 *
 * #### Table attributes
 * Tables have an associative array of attributes that
 * provide descriptive metadata for the data content.  Applications may
 * add any number and type of attributes, but this class, and others in
 * this package, recognize a few well-known attributes:
 *
 *  - 'name' (string) is a brief name of the data
 *  - 'longName' (string) is a longer more human-friendly name of the data
 *  - 'description' (string) is a block of text describing the data
 *  - 'sourceFileName' (string) is the name of the source file for the data
 *  - 'sourceSyntax' (string) is the source file base syntax
 *  - 'sourceMIMEType' (string) is the source file mime type
 *  - 'sourceSchemaName' (string) is the name of a source file schema
 *
 * All attributes are optional.
 *
 * The 'name' may be an abbreviation or acronym, while the 'longName' may
 * spell out the abbreviation or acronym.
 *
 * The 'description' may be a block of text containing several unformatted
 * sentences describing the data.
 *
 * When the data originates from a source file, the 'sourceFileName' may
 * be the name of the file. If that file's syntax does not provide a name
 * for the data, the file's name, without extensions, may be used to set
 * the name.
 *
 * In addition to the source file name, the file's MIME type may be set
 * in 'sourceMIMEType' (e.g. 'application/json'), and the equivalent file
 * syntax in 'sourceSyntax' e.g. 'json'). If the source file uses a specific
 * schema, the name of that schema is in 'sourceSchemaName' (e.g.
 * 'json-table').
 *
 *
 * #### Column attributes
 * Tables have zero or more columns. Each column has an associative array
 * of attributes that provide descriptive metadata for the column. Applications
 * may add any number and type of attributes, but this class, and others in
 * this package, recognize a few well-known attributes:
 *
 *  - 'name' (string) is a brief name of the data
 *  - 'longName' (string) is a longer more human-friendly name of the data
 *  - 'description' (string) is a block of text describing the data
 *  - 'type' (string) is a data type for all values in the column.
 *
 * All attributes are optional.
 *
 * The 'name' may be an abbreviation or acronym, while the 'longName' may
 * spell out the abbreviation or acronym.  The column 'name' is optional
 * but strongly encouraged.  If abscent, classes that format columns for a
 * specific output syntax (e.g. CSV or JSON) will create numbered column
 * names (e.g. '1', '2', etc.).
 *
 * The 'description' may be a block of text containing several unformatted
 * sentences describing the data.
 *
 * The column 'type' is optional and defaults to "any". Well-known type
 * names include:
 *  - 'any' is any type of data
 *  - 'boolean' is 'true' or 'false' values only
 *  - 'number' is floating-point values
 *  - 'integer' is integer values
 *  - 'null' is 'null' values only
 *  - 'string' is strings
 *  - 'date' is dates
 *  - 'time' is times
 *  - 'datetime' is dates with times
 *
 *
 * #### Table rows
 * A table may have zero or more rows of values with one row for each
 * column.
 *
 * Values in a row may be of any data type, but they should generally
 * match the data type indicated in the corresponding column's attributes.
 * Data types are *not* enforced and no conversions take place.
 *
 *
 * @author  David R. Nadeau / University of California, San Diego
 *
 * @date    1/28/2016
 *
 * @since   0.0.1
 *
 * @version 0.0.1  Initial development.
 *
 * @version 0.0.2  Revised to generalize table and column attributes into
 *   associative arrays instead of explicit attributes; removed column data
 *   type inference and conversion.
 *
 * @version 0.0.3  Revised to rename the 'shortName' attributes to 'name',
 *   add get*Name() method shortcuts, and fix assorted bugs.
 *
 * @version 0.0.4  Revised to subclass AbstractData and throw standard
 *   SPL exceptions.
 */
final class Table
    extends AbstractData
{
//----------------------------------------------------------------------
// Fields
//----------------------------------------------------------------------
    /**
     * @var  array $columnAttributes
     * An array with one associative array per column containing
     * named column attributes. Well-known column attributes include:
     *
     *  - 'name' is the short name of the column
     *  - 'longName' is a longer more human-friendly name of the column
     *  - 'description' is a block of text describing the column
     *  - 'type' is the data type for all values in the column
     *
     *
     * The short name may be an abbreviation or acronym, while the
     * long name may spell out the abbreviation or acronym.  Both
     * names are optional.
     *
     * The description may be a block of text containing several unformatted
     * sentences describing the column. The description is optional.
     *
     * The data type is optional and defaults to "any". Standard type
     * names include:
     *  - 'any' is any type of data
     *  - 'boolean' is 'true' or 'false' values only
     *  - 'number' is floating-point values
     *  - 'integer' is integer values
     *  - 'null' is 'null' values only
     *  - 'string' is strings
     *  - 'date' is dates
     *  - 'time' is times
     *  - 'datetime' is dates with times
     *
     * Each column's attributes array may contain additional application-
     * or file format-specific attributes.
     */
    private $columnAttributes;

    /**
     * @var  array $rows
     * An array with one array per row containing one value for each
     * column in the table. Values are of arbitrary type.
     */
    private $rows;





//----------------------------------------------------------------------
// Constants
//----------------------------------------------------------------------
    /**
     * @var array WELL_KNOWN_COLUMN_ATTRIBUTES
     * An associative array where the keys are the names of well-known
     * column attributes.
     */
    private static $WELL_KNOWN_COLUMN_ATTRIBUTES = array(
        'name' => 1,
        'longName' => 1,
        'description' => 1,
        'type' => 1
    );

    /**
     * @var array WELL_KNOWN_COLUMN_TYPES
     * An associative array where the keys are the names of well-known
     * column types.
     */
    private static $WELL_KNOWN_COLUMN_TYPES = array(
        'any' => 1,
        'boolean' => 1,
        'date' => 1,
        'datetime' => 1,
        'integer' => 1,
        'null' => 1,
        'number' => 1,
        'string' => 1,
        'time' => 1
    );


    private static $ERROR_column_attributes_argument_invalid =
        'Column attributes must be an array or object.';
    private static $ERROR_column_attribute_key_invalid =
        'Column attribute keys must be non-empty strings.';
    private static $ERROR_column_attribute_wellknown_key_value_invalid =
        'Column attribute values for well-known keys must be strings.';
    private static $ERROR_column_attribute_type_invalid =
        'Column type name is not recognized.';

    private static $ERROR_column_index_out_of_bounds =
        'Column index is out of bounds.';
    private static $ERROR_column_count_out_of_bounds =
        'Column count is out of bounds.';


    private static $ERROR_row_index_out_of_bounds =
        'Table row index is out of bounds.';
    private static $ERROR_row_count_out_of_bounds =
        'Table row count is out of bounds.';

    private static $ERROR_column_attributes_invalid =
        'Table column attributes must be an array or object.';

    private static $ERROR_rows_empty =
        'Table row array must not be empty.';
    private static $ERROR_row_invalid =
        'Table row must be an array of values with one value per column.';
    private static $ERROR_rows_invalid =
        'Table rows must be an array of arrays of values.';





//----------------------------------------------------------------------
// Constructors & Destructors
//----------------------------------------------------------------------
    /**
     * @name Constructors
     */
    // @{
    /**
     * Constructs an empty table with the given initial attribute values
     * and no rows or columns.
     *
     * @param   array $attributes  an associatve array of data attributes.
     *
     * @return  object             returns a new empty table with the
     * provided attributes.
     *
     * @throws \InvalidArgumentException  if $attributes is not an array,
     * object, or NULL, or if any of its attributes have invalid keys or
     * values.
     */
    public function __construct( $attributes = NULL )
    {
        parent::__construct( $attributes );

        $this->columnAttributes = array( );
        $this->rows = array( );
    }

    /**
     * Clones the data by doing a deep copy of all attributes and values.
     */
    public function __clone( )
    {
        // For any property that is an object or array, make a
        // deep copy by forcing a serialize, then unserialize.
        foreach ( $this as $key => &$value )
        {
            if ( is_object( $value ) || is_array( $value ) )
                $this->{$key} = unserialize( serialize( $value ) );
        }
    }
    // @}

    /**
     * @name Destructors
     */
    // @{
    /**
     * Destroys the previously constructed table.
     */
    public function __destruct( )
    {
        parent::__destruct( );
    }
    // @}





//----------------------------------------------------------------------
// Column attributes methods
//----------------------------------------------------------------------
    /**
     * @name Column attributes methods
     */
    // @{
    /**
     * Clears all attributes for the selected column without affecting
     * any other data content.
     *
     * Example:
     * @code
     *   $table->clearColumnAttributes( $index );
     * @endcode
     *
     * @param integer $columnIndex  the non-negative numeric index for
     * an existing table column.
     *
     * @throws \OutOfBoundsException  if $columnIndex is out of bounds.
     *
     * @see clearRows( ) to clear all of the rows of values in a table
     *   while retaining table and column attributes.
     *
     * @see clearAttributes( ) to clear table attributes while retaining
     *   column attributes and row values.
     */
    public function clearColumnAttributes( $columnIndex )
    {
        // Validate.
        if ( $columnIndex < 0 ||
            $columnIndex >= count( $this->columnAttributes ) )
            throw new \OutOfBoundsException(
                self::$ERROR_column_index_out_of_bounds );

        // Set to an empty array. This retains the notion that there is
        // a column, but with no attributes for the column.
        $this->columnAttributes[$columnIndex] = array( );
    }

    /**
     * Returns the column index of the first column found with the
     * selected name, or a -1 if the column is not found.
     *
     * The table columns are checked one-by-one, in order, looking for
     * the first column with a 'name' or 'longName' attribute
     * with the given name. Column names are looked up with case sensitivity.
     *
     * Example:
     * @code
     *   $index = $table->findColumnByName( 'X' );
     * @endcode
     *
     * @param string $name  the name of a column to look for in the table.
     *
     * @return integer      returns the column index of the first column
     * found with a short or long name that matches, or -1 if not found.
     *
     * @throws \InvalidArgumentException  if $name is not a non-empty string.
     */
    public function findColumnByName( $name )
    {
        // Validate.
        if ( !is_string( $name ) || $name === '' )
            throw new \InvalidArgumentException(
                self::$ERROR_column_attribute_key_invalid );

        // Sweep through the columns to find the requested column.
        $v = (string)$name;
        $n = count( $this->columnAttributes );
        for ( $i = 0; $i < $n; ++$i )
        {
            if ( isset( $this->columnAttributes[$i]['name'] ) )
            {
                $name = $this->columnAttributes[$i]['name'];
                if ( $name == $v )
                    return $i;
            }

            if ( isset( $this->columnAttributes[$i]['longName'] ) )
            {
                $longName = $this->columnAttributes[$i]['longName'];
                if ( $longName == $v )
                    return $i;
            }
        }
        return -1;
    }

    /**
     * Returns a copy of the value of the selected column attribute
     * for the selected column, or NULL if the attribute is not found.
     *
     * Example:
     * @code
     *   $name = $table->getColumnAttribute( $index, 'name' );
     * @endcode
     *
     * @param integer $columnIndex  the non-negative numeric index for
     * an existing table column.
     *
     * @param string $key  the key for a column attribute to query.
     *
     * @return mixed  returns a copy of the value for the selected
     * column and attribute.
     *
     * @throws \OutOfBoundsException  if $columnIndex is out of bounds.
     *
     * @throws \InvalidArgumentException  if $key is not a non-empty string.
     */
    public function getColumnAttribute( $columnIndex, $key )
    {
        // Validate.
        if ( !is_string( $key ) || $key === '' )
            throw new \InvalidArgumentException(
                self::$ERROR_column_attribute_key_invalid );
        if ( $columnIndex < 0 ||
            $columnIndex >= count( $this->columnAttributes ) )
            throw new \OutOfBoundsException(
                self::$ERROR_column_index_out_of_bounds );

        // Get
        if ( !isset( $this->columnAttributes[$columnIndex][$key] ) )
            return NULL;                // Key not found
        return $this->columnAttributes[$columnIndex][$key];
    }

    /**
     * Returns an array of keywords found in the column's attributes,
     * including the name, long name, description, and other attributes.
     *
     * Such a keyword list is useful when building a search index to
     * find this data object. The returns keywords array is in
     * lower case, with duplicate words removed, and the array sorted
     * in a natural sort order.
     *
     * The keyword list is formed by extracting all space or punctuation
     * delimited words found in all attribute keys and values. This
     * includes the name, long name, and description attributes, and
     * any others added by the application. Well known attribute
     * names, such as 'name', 'longName', etc., are not included, but
     * application-specific attribute names are included.
     *
     * Numbers and punctuation are ignored. Array and object attribute
     * values are converted to text and then scanned for words.
     *
     * @param integer $columnIndex  the non-negative numeric index for
     * an existing table column.
     *
     * @return array  returns an array of keywords.
     *
     * @throws \OutOfBoundsException  if $columnIndex is out of bounds.
     */
    public function getColumnAttributeKeywords( $columnIndex )
    {
        // Add all column attribute keys and values for one column.
        $text = '';
        foreach ( $this->columnAttributes[$columnIndex] as $key => &$value )
        {
            // Add the key. Skip well-known key names.  Intelligently
            // convert to text.
            if ( !isset( self::$WELL_KNOWN_COLUMN_ATTRIBUTES[$key] ) )
                $text .= ' ' . $this->valueToText( $key );

            // Add the value.  Intelligently convert to text.
            $text .= ' ' . $this->valueToText( $value );
        }

        // Clean the text of numbers and punctuation, and return
        // an array of keywords.
        return $this->textToKeywords( $text );
    }

    /**
     * Returns an array of keywords found in all column attributes,
     * including the name, long name, description, and other attributes.
     *
     * Such a keyword list is useful when building a search index to
     * find this data object. The returns keywords array is in
     * lower case, with duplicate words removed, and the array sorted
     * in a natural sort order.
     *
     * The keyword list is formed by extracting all space or punctuation
     * delimited words found in all attribute keys and values. This
     * includes the name, long name, and description attributes, and
     * any others added by the application. Well known attribute
     * names, such as 'name', 'longName', etc., are not included, but
     * application-specific attribute names are included.
     *
     * Numbers and punctuation are ignored. Array and object attribute
     * values are converted to text and then scanned for words.
     *
     * @return array  returns an array of keywords.
     */
    public function getAllColumnAttributeKeywords( )
    {
        // Add all column attribute keys and values for all columns.
        $text = '';
        foreach ( $this->columnAttributes as &$att )
        {
            foreach ( $att as $key => &$value )
            {
                // Add the key. Skip well-known key names.  Intelligently
                // convert to text.
                if ( !isset( self::$WELL_KNOWN_COLUMN_ATTRIBUTES[$key] ) )
                    $text .= ' ' . $this->valueToText( $key );

                // Add the value.  Intelligently convert to text.
                $text .= ' ' . $this->valueToText( $value );
            }
        }

        // Clean the text of numbers and punctuation, and return
        // an array of keywords.
        return $this->textToKeywords( $text );
    }

    /**
     * Returns an associative array containing a copy of all attributes
     * for the selected column.
     *
     * If the column has no attributes, an empty array is returned.
     *
     * Example:
     * @code
     *   $attributes = $table->getColumnAttributes( $index );
     *   foreach ( $attributes as $key => $value )
     *   {
     *     print( "$key = $value\n" );
     *   }
     * @endcode
     *
     * @param integer $columnIndex  the non-negative numeric index for
     * an existing table column.
     *
     * @return array  returns an associative array of named attributes
     * associatied with the column, or an empty array if there are no
     * attributes or the column index is out of bounds.
     *
     * @throws \OutOfBoundsException  if $columnIndex is out of bounds.
     */
    public function getColumnAttributes( $columnIndex )
    {
        // Validate
        if ( $columnIndex < 0 ||
            $columnIndex >= count( $this->columnAttributes ) )
            throw new \OutOfBoundsException(
                self::$ERROR_column_index_out_of_bounds );

        // Get
        return $this->columnAttributes[$columnIndex];
    }

    /**
     * Returns a "best" column name by checking for, in order, the long name
     * and short name, and returning the first non-empty value
     * found, or the column number if all of those are empty.
     *
     * Example:
     * @code
     *   $bestName = $data->getColumnBestName( $columnIndex );
     * @endcode
     *
     * This method is a convenience function that is the equivalent of
     * checking each of the long name and name attributes in order.
     *
     * @param integer $columnIndex  the non-negative numeric index for
     * an existing table column.
     *
     * @return  the best name
     *
     * @throws \OutOfBoundsException  if $columnIndex is out of bounds.
     */
    public function getColumnBestName( $columnIndex )
    {
        $v = $this->getColumnAttribute( $columnIndex, 'longName' );
        if ( $v !== '' && $v !== NULL )
            return strval( $v );
        $v = $this->getColumnAttribute( $columnIndex, 'name' );
        if ( $v !== '' && $v !== NULL )
            return strval( $v );
        return strval( $columnIndex );
    }

    /**
     * Returns the description of the selected column, or an empty string if
     * the column has no description.
     *
     * Example:
     * @code
     *   $description = $table->getColumnDescription( $index );
     * @endcode
     *
     * This method is a convenience function that is the equivalent of
     * getting the column's 'description' attribute.
     *
     * @param integer $columnIndex  the non-negative numeric index for
     * an existing table column.
     *
     * @return  the column description, or an empty string if there is
     * no description.
     *
     * @throws \OutOfBoundsException  if $columnIndex is out of bounds.
     */
    public function getColumnDescription( $columnIndex )
    {
        $v = $this->getColumnAttribute( $columnIndex, 'description' );
        if ( $v !== '' && $v !== NULL )
            return strval( $v );
        return '';
    }

    /**
     * Returns the long name of the selected column, or an empty string if
     * the column has no long name.
     *
     * Example:
     * @code
     *   $longName = $table->getColumnLongName( $index );
     * @endcode
     *
     * This method is a convenience function that is the equivalent of
     * getting the column's 'longName' attribute.
     *
     * @param integer $columnIndex  the non-negative numeric index for
     * an existing table column.
     *
     * @return  the column long name, or an empty string if there is no
     * long name.
     *
     * @throws \OutOfBoundsException  if $columnIndex is out of bounds.
     */
    public function getColumnLongName( $columnIndex )
    {
        $v = $this->getColumnAttribute( $columnIndex, 'longName' );
        if ( $v !== '' && $v !== NULL )
            return strval( $v );
        return '';
    }

    /**
     * Returns the name of the selected column, or an empty string if
     * the column has no name.
     *
     * Example:
     * @code
     *   $name = $table->getColumnName( $index );
     * @endcode
     *
     * This method is a convenience function that is the equivalent of
     * getting the column's 'name' attribute.
     *
     * @param integer $columnIndex  the non-negative numeric index for
     * an existing table column.
     *
     * @return  the column name, or an empty string if there is no name.
     *
     * @throws \OutOfBoundsException  if $columnIndex is out of bounds.
     */
    public function getColumnName( $columnIndex )
    {
        $v = $this->getColumnAttribute( $columnIndex, 'name' );
        if ( $v !== '' && $v !== NULL )
            return strval( $v );
        return '';
    }

    /**
     * Returns the data type of the selected column, or an empty string if
     * the column has no data type.
     *
     * Example:
     * @code
     *   $type = $table->getColumnType( $index );
     * @endcode
     *
     * This method is a convenience function that is the equivalent of
     * getting the column's 'type' attribute.
     *
     * @param integer $columnIndex  the non-negative numeric index for
     * an existing table column.
     *
     * @return  the column type, or an empty string if there is no type.
     *
     * @throws \OutOfBoundsException  if $columnIndex is out of bounds.
     */
    public function getColumnType( $columnIndex )
    {
        $v = $this->getColumnAttribute( $columnIndex, 'type' );
        if ( is_null( $v ) )
            return '';
        return $v;
    }





    /**
     * Returns true if all values in the selected column are strings.
     *
     * @param integer $columnIndex  the non-negative numeric index for
     * an existing table column.
     *
     * @return  true if all column values are strings
     *
     * @throws \OutOfBoundsException  if $columnIndex is out of bounds.
     */
    public function isColumnStrings( $columnIndex )
    {
        if ( $columnIndex < 0 ||
            $columnIndex >= count( $this->columnAttributes ) )
            throw new \OutOfBoundsException(
                self::$ERROR_column_index_out_of_bounds );

        if ( count( $this->rows ) == 0 )
            return false;
        foreach ( $this->rows as &$row )
        {
            if ( !is_string( $row[$columnIndex] ) )
                return false;
        }
        return true;
    }

    /**
     * Returns true if all values in the selected column are numbers
     * (not strings that can be parsed as numbers, or objects with
     * a string representation as a number).
     *
     * @param integer $columnIndex  the non-negative numeric index for
     * an existing table column.
     *
     * @return  true if all column values are numeric
     *
     * @throws \OutOfBoundsException  if $columnIndex is out of bounds.
     */
    public function isColumnNumeric( $columnIndex )
    {
        if ( $columnIndex < 0 ||
            $columnIndex >= count( $this->columnAttributes ) )
            throw new \OutOfBoundsException(
                self::$ERROR_column_index_out_of_bounds );

        if ( count( $this->rows ) == 0 )
            return false;
        foreach ( $this->rows as &$row )
        {
            if ( !(is_int( $row[$columnIndex] ) ||
                is_float( $row[$columnIndex] ) ) )
                return false;
        }
        return true;
    }





    /**
     * Sets the value for the selected column attribute for the selected
     * column, overwriting any prior value or adding the attribute if it
     * was not already present.
     *
     * Attribute keys must be strings.
     *
     * Attribute values for well-known attributes must be strings.
     *
     * Attribute values for the 'type' attribute must be one of the
     * well-known type names.
     *
     * Example:
     * @code
     *   $table->setColumnAttribute( $index, 'name', 'Total' );
     * @endcode
     *
     * @param integer $columnIndex  the non-negative numeric index for
     * an existing table column.
     *
     * @param string  $key  the key of a column attribute.
     *
     * @param mixed   $value  the value of a column attribute.
     *
     * @throws \OutOfBoundsException  if $columnIndex is out of bounds.
     *
     * @throws \InvalidArgumentException  if $key is not a string or is empty,
     * or if $value is not a string when $key is one of the well-known
     * attributes, or if $key is 'type' but $value is not a well-known
     * data type.
     */
    public function setColumnAttribute( $columnIndex, $key, $value )
    {
        // Validate
        if ( $columnIndex < 0 ||
            $columnIndex >= count( $this->columnAttributes ) )
            throw new \OutOfBoundsException(
                self::$ERROR_column_index_out_of_bounds );
        if ( !is_string( $key ) || $key === '' )
            throw new \InvalidArgumentException(
                self::$ERROR_column_attribute_key_invalid );
        if ( isset( self::$WELL_KNOWN_COLUMN_ATTRIBUTES[$key] ) &&
            !is_string( $value ) )
            throw new \InvalidArgumentException(
                self::$ERROR_column_attribute_wellknown_key_value_invalid );
        if ( $key == 'type' &&
            !isset( self::$WELL_KNOWN_COLUMN_TYPES[$value] ) )
            throw new \InvalidArgumentException(
                self::$ERROR_column_attribute_type_invalid );

        $this->columnAttributes[$columnIndex][$key] = $value;
    }

    /**
     * Sets the values for the selected column attributes for the selected
     * column, overwriting any prior values or adding attributes if they
     * were not already present.
     *
     * Attribute keys must be strings.
     *
     * Attribute values for well-known attributes must be strings.
     *
     * Attribute values for the 'type' attribute must be one of the
     * well-known type names.
     *
     * Example:
     * @code
     *   $attributes = array( 'name' => 'Total' );
     *   $table->setColumnAttributes( $index, $attributes );
     * @endcode
     *
     * @param integer $columnIndex  the non-negative numeric index for
     * an existing table column.
     *
     * @param   array $attributes  an associatve array of named
     * attributes associated with the column.
     *
     * @throws \OutOfBoundsException  if $columnIndex is out of bounds.
     *
     * @throws \InvalidArgumentException  if $attributes is not an array,
     * object, or NULL, or if any of its attributes have invalid keys or
     * values.
     */
    public function setColumnAttributes( $columnIndex, $attributes )
    {
        // Validate
        if ( !is_array( $attributes ) && !is_object( $attributes ) &&
            $attributes != NULL )
            throw new \InvalidArgumentException(
                self::$ERROR_column_attributes_argument_invalid );
        if ( $columnIndex < 0 ||
            $columnIndex >= count( $this->columnAttributes ) )
            throw new \OutOfBoundsException(
                self::$ERROR_column_index_out_of_bounds );
        if ( empty( $attributes ) )
            return;                     // Request to set with nothing

        // Convert object argument to an array, if needed.
        $a = (array)$attributes;

        // Insure keys are all strings and all well-known key values
        // are strings.
        foreach ( $a as $key => $value )
        {
            if ( !is_string( $key ) || $key === '' )
                throw new \InvalidArgumentException(
                    self::$ERROR_column_attribute_key_invalid );

            if ( isset( self::$WELL_KNOWN_COLUMN_ATTRIBUTES[$key] ) &&
                !is_string( $value ) )
                throw new \InvalidArgumentException(
                    self::$ERROR_column_attribute_wellknown_key_value_invalid );

            if ( $key == 'type' &&
                !isset( self::$WELL_KNOWN_COLUMN_TYPES[$value] ) )
                throw new \InvalidArgumentException(
                    self::$ERROR_column_attribute_type_invalid );
        }

        // Set.
        foreach ( $a as $key => $value )
        {
            $this->columnAttributes[$columnIndex][$key] = $value;
        }
    }
    // @}





//----------------------------------------------------------------------
// Table methods
//----------------------------------------------------------------------
    /**
     * @name Table methods
     */
    // @{
    /**
     * Clears the entire table, removing all rows of values, all table
     * attributes, and all column attributes, leaving an entirely
     * empty table.
     *
     * This method is equivalent to clearing all table attributes, then
     * deleting all columns (and thus all values in all table rows):
     * @code
     *   $table->clearAttributes( );
     *   $table->deleteColumns( 0, $table->getNumberOfColumns( ) );
     * @endcode
     *
     * Example:
     * @code
     *   $table->clear( );
     * @endcode
     *
     * @see clearRows( ) to clear all of the rows of values in a table
     *   while retaining table and column attributes.
     *
     * @see clearAttributes( ) to clear table attributes while retaining
     *   column attributes and row values.
     *
     * @see deleteColumns( ) to delete all columns in the table, including
     *   all values in all rows and all column attributes, while
     *   retaining table attributes.
     */
    public function clear( )
    {
        $this->clearAttributes( );
        $this->rows       = array( );   // Clear all rows of values
        $this->columnAttributes = array( );// Clear all column attributes
    }
    // @}





//----------------------------------------------------------------------
// Column operations
//----------------------------------------------------------------------
    /**
     * @name Column operations
     */
    // @{
    /**
     * Appends a column with the given attributes to the end of the
     * list of columns, and adds a column of values to all rows,
     * initializing values to the given default value.
     *
     * Example:
     * @code
     *   $attributes = array(
     *     'name' => 'X',
     *     'description' => 'X coordinates'
     *   );
     *   $table->appendColumn( $attributes, 0 );
     * @endcode
     *
     * @param   array $attributes  an associatve array of named
     * attributes associated with the column.
     *
     * @param   mixed $defaultValue the default value used to initialize
     * the new column's value in all rows.
     *
     * @return  integer  returns the column index for the new column.
     *
     * @throws \InvalidArgumentException  if $attributes is not an array,
     * object, or NULL, or if any of its attributes have invalid keys or
     * values.
     */
    public function appendColumn( $attributes = NULL, $defaultValue = 0 )
    {
        // Add the column.
        $columnIndex = count( $this->columnAttributes );
        $this->columnAttributes[] = array( );

        // Set the attributes. This may throw an exception if the
        // attributes are bad.
        try
        {
            $this->setColumnAttributes( $columnIndex, $attributes );
        }
        catch ( \Exception $e )
        {
            // Delete the added column, then rethrow the exception.
            array_splice( $this->columnAttributes, $columnIndex, 1 );
            throw $e;
        }

        // Add a value to all rows.
        foreach ( $this->rows as &$row )
            $row[] = $defaultValue;

        return $columnIndex;
    }

    /**
     * Deletes the selected column, its column attributes, and the
     * corresponding column value from each row in the table.
     *
     * Example:
     * @code
     *   $table->deleteColumn( $index );
     * @endcode
     *
     * @param integer $columnIndex  the non-negative numeric index for
     * an existing table column.
     *
     * @throws \OutOfBoundsException  if $columnIndex is out of bounds.
     *
     * @see deleteColumns( ) to delete more than one adjacent column.
     */
    public function deleteColumn( $columnIndex )
    {
        $this->deleteColumns( $columnIndex, 1 );
    }

    /**
     * Deletes the selected adjacent columns, their column attributes, and the
     * corresponding values from each row in the table.
     *
     * Example:
     * @code
     *   $table->deleteColumns( $index, $number );
     * @endcode
     *
     * To delete all columns in a table:
     * @code
     *   $table->deleteColumns( 0, $table->getNumberOfColumns( ) );
     * @endcode
     *
     * @param integer $columnIndex  the non-negative numeric index for
     * an existing table column.
     *
     * @param integer $numberOfColumns  the non-negative number of columns to
     * delete.
     *
     * @throws \OutOfBoundsException  if $columnIndex is out of bounds,
     * or $numberOfColumns is out of bounds.
     */
    public function deleteColumns( $columnIndex, $numberOfColumns = 1 )
    {
        // Validate
        $nColumns = count( $this->columnAttributes );
        if ( $columnIndex < 0 ||
            $columnIndex >= $nColumns )
            throw new \OutOfBoundsException(
                self::$ERROR_column_index_out_of_bounds );
        if ( $numberOfColumns == 0 )
            return;                     // Nothing to delete
        if ( $numberOfColumns < 0 ||
            ($columnIndex + $numberOfColumns) > $nColumns )
            throw new \OutOfBoundsException(
                self::$ERROR_column_count_out_of_bounds );

        // Delete the columns from the column attributes array.
        array_splice( $this->columnAttributes, $columnIndex, $numberOfColumns );

        // Run through all rows and delete the columns from each row.
        foreach ( $this->rows as &$row )
            array_splice( $row, $columnIndex, $numberOfColumns );
    }

    /**
     * Returns an array of values for the selected column and all rows
     * of the table.
     *
     * Example:
     * @code
     *   $values = $table->getColumnValues( $index );
     * @endcode
     *
     * @param integer $columnIndex  the non-negative numeric index for
     * an existing table column.
     *
     * @return array  returns an array of values with one value for each
     * table row for the selected column.
     *
     * @throws \OutOfBoundsException  if $columnIndex is out of bounds.
     */
    public function getColumnValues( $columnIndex )
    {
        // Validate
        if ( $columnIndex < 0 ||
            $columnIndex >= count( $this->columnAttributes ) )
            throw new \OutOfBoundsException(
                self::$ERROR_column_index_out_of_bounds );

        // Set
        $column = array( );
        foreach ( $this->rows as &$row )
            $column[] = $row[$columnIndex];
        return $column;
    }

    /**
     * Returns the number of columns.
     *
     * Example:
     * @code
     *   $n = $table->getNumberOfColumns( );
     *   for ( $i = 0; $i < $n; ++$i )
     *   {
     *     $name = $table->getColumnName( $i );
     *     print( "$i:  $name\n" )(;
     *   }
     * @endcode
     *
     * @return integer the number of columns.
     */
    public function getNumberOfColumns( )
    {
        return count( $this->columnAttributes );
    }

    /**
     * Inserts a column with the given attributes at the selected
     * column index in the list of columns, and adds a column of values
     * to all rows, initializing values to the given default value.
     *
     * Example:
     * @code
     *   $attributes = array( 'name' => 'Y' );
     *   $table->insertColumn( $index, $attributes, 0 );
     * @endcode
     *
     * @param integer $columnIndex  the non-negative numeric index for
     * a new table column.
     *
     * @param   array $attributes  an associatve array of named
     * attributes associated with the column.
     *
     * @param   mixed $defaultValue the default value used to initialize
     * the new column's value in all rows.
     *
     * @return  integer  returns the column index for the new column.
     *
     * @throws \OutOfBoundsException  if $columnIndex is out of bounds.
     *
     * @throws \InvalidArgumentException  if $attributes is not an array,
     * object, or NULL, or if any of its attributes have invalid keys or
     * values.
     */
    public function insertColumn( $columnIndex, $attributes = NULL,
        $defaultValue = 0 )
    {
        // Validate.
        $nColumns = count( $this->columnAttributes );
        if ( $columnIndex < 0 || $columnIndex > $nColumns )
            throw new \OutOfBoundsException(
                self::$ERROR_column_index_out_of_bounds );

        // Insert an empty column's attributes.
        if ( $columnIndex == $nColumns )
            $this->columnAttributes[] = array( );
        else
            array_splice( $this->columnAttributes, $columnIndex, 0,
                array( array( ) ) );

        // Set the attributes. This may throw an exception if the
        // attributes are bad.
        try
        {
            $this->setColumnAttributes( $columnIndex, $attributes );
        }
        catch ( \Exception $e )
        {
            // Delete the added column, then rethrow the exception.
            array_splice( $this->columnAttributes, $columnIndex, 1 );
            throw $e;
        }

        // Set.
        if ( $columnIndex == $nColumns )
        {
            foreach ( $this->rows as &$row )
                $row[] = $defaultValue;
        }
        else
        {
            foreach ( $this->rows as &$row )
                array_splice( $row, $columnIndex, 0, array( $defaultValue ) );
        }
        return $columnIndex;
    }

    /**
     * Moves a selected column to a new location before or after the
     * the column.
     *
     * Example:
     * @code
     *   $table->moveColumn( $from, $to );
     * @endcode
     *
     * @param integer $fromColumnIndex  the non-negative numeric index for
     * the existing column to move.
     *
     * @param integer $toColumnIndex  the non-negative numeric index at
     * which the column should be placed.
     *
     * @throws \OutOfBoundsException  if $fromColumnIndex or $toColumnIndex
     * is out of bounds.
     */
    public function moveColumn( $fromColumnIndex, $toColumnIndex )
    {
        // Validate.
        $nColumns = count( $this->columnAttributes );
        if ( $fromColumnIndex < 0 || $fromColumnIndex >= $nColumns )
            throw new \OutOfBoundsException(
                self::$ERROR_column_index_out_of_bounds );
        if ( $toColumnIndex < 0 || $toColumnIndex >= $nColumns )
            throw new \OutOfBoundsException(
                self::$ERROR_column_index_out_of_bounds );
        if ( $fromColumnIndex == $toColumnIndex )
            return;                     // Request to move to same location

        // Move.
        if ( $fromColumnIndex < $toColumnIndex )
        {
            // Move towards end.
            $ca = $this->columnAttributes[$fromColumnIndex];
            for ( $i = $fromColumnIndex; $i < $toColumnIndex; $i++ )
                $this->columnAttributes[$i] = $this->columnAttributes[$i+1];
            $this->columnAttributes[$toColumnIndex] = $ca;

            foreach ( $this->rows as &$row )
            {
                $v = $row[$fromColumnIndex];
                for ( $i = $fromColumnIndex; $i < $toColumnIndex; $i++ )
                    $row[$i] = $this->columnAttributes[$i+1];
                $row[$toColumnIndex] = $v;
            }
        }
        else
        {
            // Move towards start.
            $ca = $this->columnAttributes[$fromColumnIndex];
            for ( $i = $fromColumnIndex; $i > $toColumnIndex; $i-- )
                $this->columnAttributes[$i] = $this->columnAttributes[$i-1];
            $this->columnAttributes[$toColumnIndex] = $ca;

            foreach ( $this->rows as &$row )
            {
                $v = $row[$fromColumnIndex];
                for ( $i = $fromColumnIndex; $i > $toColumnIndex; $i-- )
                    $row[$i] = $this->columnAttributes[$i-1];
                $row[$toColumnIndex] = $v;
            }
        }
    }
    // @}





//----------------------------------------------------------------------
// Row operations
//----------------------------------------------------------------------
    /**
     * @name Row operations
     */
    // @{
    /**
     * Appends a new row of values to the end of the table.
     *
     * The method's $row argument must be an array of values with one
     * value per column in the table. The array's values are copied
     * into a new last row of the table.
     *
     * Example:
     * @code
     *   $row = array( 1, 2, 3 );
     *   $table->appendRow( $row );
     * @endcode
     *
     * @param array $row  an array of values with one value for each
     * table column.
     *
     * @return integer    returns the non-negative row index of the
     * new row.
     *
     * @throws \InvalidArgumentException  if $row is not an array, or if
     * it does not have one value for each table column.
     */
    public function appendRow( $row )
    {
        // Validate.
        if ( !is_array( $row ) ||
            count( $row ) != count( $this->columnAttributes ) )
            throw new \InvalidArgumentException( self::$ERROR_row_invalid );

        // Set.
        $this->rows[] = $row;
        return count( $this->rows ) - 1;
    }

    /**
     * Appends an array of arrays of new row of values to the end of
     * the table.
     *
     * The method's $row argument should be an array of row arrays where
     * each row array has one value per column in the table. The array's
     * values are copied into new rows at the end of the table.
     *
     * Example:
     * @code
     *   $rows = array( );
     *   $rows[] = array( 1, 2, 3 );
     *   $rows[] = array( 4, 5, 6 );
     *   $table->appendRows( $rows );
     * @endcode
     *
     * @param array $rows  an array of arrays of values with one value for each
     * table column.
     *
     * @return integer    returns the non-negative row index of the
     * first new row.
     *
     * @throws \InvalidArgumentException  if $rows is not an array,
     * it's an empty array, if any entry in the array is not an array, or
     * if any array in 4rows does not have one value for each table column.
     */
    public function appendRows( &$rows )
    {
        // Validate.
        if ( !is_array( $rows ) )
            throw new \InvalidArgumentException(
                self::$ERROR_rows_invalid );
        if ( count( $rows ) == 0 )
            throw new \InvalidArgumentException(
                self::$ERROR_rows_empty );

        $nColumns = count( $this->columnAttributes );
        foreach ( $rows as &$row )
            if ( !is_array( $row ) || count( $row ) != $nColumns )
                throw new \InvalidArgumentException(
                    self::$ERROR_row_invalid );

        // Set.
        $newRowIndex = count( $this->rows );
        foreach ( $rows as &$row )
            $this->rows[] = $row;
        return $newRowIndex;
    }

    /**
     * Clears the table of all rows of values while retaining table
     * attributes and column attributes.
     *
     * Example:
     * @code
     *   $table->clearRows( );
     * @endcode
     *
     * This is equivalent to:
     * @code
     *  $table->deleteRows( 0, $table->getNumberOfRows( ) );
     * @endcode
     *
     * @see clearAttributes( ) to clear table attributes
     * @see clearColumnAttributes( ) to clear column attributes
     */
    public function clearRows( )
    {
        $this->rows = array( );
    }

    /**
     * Deletes a selected row from the table.
     *
     * Example:
     * @code
     *   $table->deleteRow( $index );
     * @endcode
     *
     * @param integer $rowIndex the non-negative row index of the row
     * to delete.
     *
     * @throws \OutOfBoundsException  if $rowIndex is out of bounds.
     */
    public function deleteRow( $rowIndex )
    {
        $this->deleteRows( $rowIndex, 1 );
    }

    /**
     * Deletes a selected range of rows from the table.
     *
     * Example:
     * @code
     *   $table->deleteRows( $index, $count );
     * @endcode
     *
     * @param integer $rowIndex the non-negative row index of the
     * first row to delete.
     *
     * @param integer $numberOfRows the non-negative number of rows to
     * delete.
     *
     * @throws \OutOfBoundsException  if $rowIndex is out of bounds, or if
     * $numberOfRows is out of bounds.
     */
    public function deleteRows( $rowIndex, $numberOfRows = 1 )
    {
        // Validate
        $nRows = count( $this->rows );
        if ( $rowIndex < 0 || $rowIndex >= $nRows )
            throw new \OutOfBoundsException(
                self::$ERROR_row_index_out_of_bounds );
        if ( $numberOfRows == 0 )
            return;                     // Nothing to delete
        if ( $numberOfRows < 0 || ($rowIndex + $numberOfRows) >= $nRows )
            throw new \OutOfBoundsException(
                self::$ERROR_row_count_out_of_bounds );

        // Unset.
        array_splice( $this->rows, $rowIndex, $numberOfRows );
    }

    /**
     * Returns the number of rows in the table.
     *
     * Example:
     * @code
     *   $n = $table->getNumberOfRows( );
     * @endcode
     *
     * @return integer  returns the non-negative number of rows in the table.
     */
    public function getNumberOfRows( )
    {
        return count( $this->rows );
    }

    /**
     * Returns an array of keywords found in the table's rows.
     *
     * Such a keyword list is useful when building a search index to
     * find this data object. The returns keywords array is in
     * lower case, with duplicate words removed, and the array sorted
     * in a natural sort order.
     *
     * The keyword list is formed by extracting all space or punctuation
     * delimited words found in all row values.  Numbers and punctuation
     * are ignored. Array and object values are converted to text and
     * then scanned for words.
     *
     * @return array  returns an array of keywords.
     */
    public function getAllRowKeywords( )
    {
        // Add all values for all rows and columns.
        $text = '';
        foreach ( $this->rows as &$row )
        {
            foreach ( $row as &$value )
            {
                // Add the value.  Intelligently convert to text.
                $text .= ' ' . $this->valueToText( $value );
            }
        }

        // Clean the text of numbers and punctuation, and return
        // an array of keywords.
        return $this->textToKeywords( $text );
    }

    /**
     * Returns an array containing a copy of the values in the selected
     * row of the table.
     *
     * Example:
     * @code
     *   $values = $table->getRowValues( $index );
     * @endcode
     *
     * @param integer $rowIndex the non-negative row index of the
     * row to get.
     *
     * @return array  returns an array of values with one value for
     * each column of the table.
     *
     * @throws \OutOfBoundsException  if $rowIndex is out of bounds.
     */
    public function getRowValues( $rowIndex )
    {
        // Validate.
        if ( $rowIndex < 0 || $rowIndex >= count( $this->rows ) )
            throw new \OutOfBoundsException(
                self::$ERROR_row_index_out_of_bounds );

        // Get.
        return $this->rows[$rowIndex];
    }

    /**
     * Inserts a row of values into the table so that the new row
     * has the selected row index.
     *
     * The method's $row argument should be an array of values with one
     * value per column in the table. The array's values are copied
     * into the table.
     *
     * Example:
     * @code
     *   $row = array( 1, 2, 3 );
     *   $table->insertRow( $index, $row );
     * @endcode
     *
     * @param integer $rowIndex the non-negative row index of the
     * row insert point.
     *
     * @param array $row  an array of values with one value for each
     * table column.
     *
     * @return integer    returns the non-negative row index of the
     * new row.
     *
     * @throws \OutOfBoundsException  if $rowIndex is out of bounds.
     *
     * @throws \InvalidArgumentException  if $row is not an array, or if
     * it does not have one value for each table column.
     */
    public function insertRow( $rowIndex, $row )
    {
        $a = array( $row );
        return $this->insertRows( $rowIndex, $a );
    }

    /**
     * Inserts an array of arrays that each contain a row of values to insert
     * into the table so that the first new row has the selected row index.
     *
     * The method's $rows argument should be an array of values with one
     * value per column in the table. The array's values are copied
     * into the table.
     *
     * Example:
     * @code
     *   $rows = array( );
     *   $rows[] = array( 1, 2, 3 );
     *   $rows[] = array( 4, 5, 6 );
     *   $table->insertRows( $index, $rows );
     * @endcode
     *
     * @param integer $rowIndex the non-negative row index of the
     * row insert point.
     *
     * @param array $rows  an array of arrays of values with one value for each
     * table column.
     *
     * @return integer    returns the non-negative row index of the
     * new row.
     *
     * @throws \OutOfBoundsException  if $rowIndex is out of bounds.
     *
     * @throws \InvalidArgumentException  if $rows is not an array,
     * it's an empty array, if any entry in the array is not an array, or
     * if any array in 4rows does not have one value for each table column.
     */
    public function insertRows( $rowIndex, &$rows )
    {
        // Validate.
        $nRows = count( $this->rows );
        if ( $rowIndex < 0 || $rowIndex > $nRows )
            throw new \OutOfBoundsException(
                self::$ERROR_row_index_out_of_bounds );
        if ( !is_array( $rows ) )
            throw new \InvalidArgumentException(
                self::$ERROR_rows_invalid );
        if ( count( $rows ) == 0 )
            throw new \InvalidArgumentException(
                self::$ERROR_rows_empty );

        $nColumns = count( $this->columnAttributes );
        foreach ( $rows as &$row )
            if ( !is_array( $row ) || count( $row ) != $nColumns )
                throw new \InvalidArgumentException(
                    self::$ERROR_row_invalid );

        // Set.
        if ( $rowIndex == $nRows )
            foreach ( $rows as &$row )
                $this->rows[] = $row;
        else
            array_splice( $this->rows, $rowIndex, 0, $rows );
        return $rowIndex;
    }

    /**
     * Moves a selected row of values from the table to a new selected
     * row position.
     *
     * Example:
     * @code
     *   $table->moveRow( $from, $to );
     * @endcode
     *
     * @param integer $fromRowIndex the non-negative row index of the
     * row to move.
     *
     * @param integer $toRowIndex the non-negative row index of the
     * new position of the row.
     *
     * @throws \OutOfBoundsException  if $fromRowIndex or $toRowIndex are
     * out of bounds.
     */
    public function moveRow( $fromRowIndex, $toRowIndex )
    {
        $this->moveRows( $fromRowIndex, $toRowIndex, 1 );
    }

    /**
     * Moves a selected row of values from the table to a new selected
     * row position.
     *
     * Example:
     * @code
     *   $table->moveRows( $from, $to, $count );
     * @endcode
     *
     * @param integer $fromRowIndex the non-negative row index of the
     * row to move.
     *
     * @param integer $toRowIndex the non-negative row index of the
     * new position of the row.
     *
     * @param integer $numberOfRows the positive number of rows to move.
     *
     * @throws \OutOfBoundsException  if $fromRowIndex or $toRowIndex are out
     * of bounds, or if $numberOfRows is out of bounds.
     */
    public function moveRows( $fromRowIndex, $toRowIndex, $numberOfRows = 1 )
    {
        // Validate
        $nRows = count( $this->rows );
        if ( $fromRowIndex < 0 || $fromRowIndex >= $nRows )
            throw new \OutOfBoundsException(
                self::$ERROR_row_index_out_of_bounds );
        if ( $toRowIndex < 0 || $toRowIndex >= $nRows )
            throw new \OutOfBoundsException(
                self::$ERROR_row_index_out_of_bounds );
        if ( $numberOfRows == 0 )
            return;                     // Nothing to move
        if ( $numberOfRows < 0 || ($fromRowIndex + $numberOfRows) > $nRows )
            throw new \OutOfBoundsException(
                self::$ERROR_row_count_out_of_bounds );
        if ( $fromRowIndex == $toRowIndex )
            return;                         // Request to move to same location

        // Move
        $mv = array_splice( $this->rows, $fromRowIndex, $numberOfRows );
        if ( $fromRowIndex < $toRowIndex )
            array_splice( $this->rows, $toRowIndex, 0, $mv );
        else
            array_splice( $this->rows, $toRowIndex, 0, $mv );
    }
    // @}





//----------------------------------------------------------------------
// Cell values methods
//----------------------------------------------------------------------
    /**
     * @name Cell values methods
     */
    // @{
    /**
     * Returns the table value at the selected row and column.
     *
     * Example:
     * @code
     *   $nRows = $table->getNumberOfRows( );
     *   $nCols = $table->getNumberOfColumns( );
     *   for ( $i = 0; $i < $nRows; ++$i )
     *   {
     *     for ( $j = 0; $j < $nCols; ++$j )
     *     {
     *       $v = $table->getValue( $i, $j );
     *       print( "$v " );
     *     }
     *     print( "\n" );
     *   }
     * @endcode
     *
     * @param integer  $rowIndex    the non-negative row index of the
     * row to query.
     *
     * @param integer  $columnIndex the non-negative column index of the
     * column to query.
     *
     * @return mixed   returns the value at the selected row and column.
     *
     * @throws \OutOfBoundsException if $rowIndex or $columnIndex are
     * out of bounds.
     */
    public function getValue( $rowIndex, $columnIndex )
    {
        // Validate.
        if ( $rowIndex < 0 || $rowIndex >= count( $this->rows ) )
            throw new \OutOfBoundsException(
                self::$ERROR_row_index_out_of_bounds );
        if ( $columnIndex < 0 ||
            $columnIndex >= count( $this->columnAttributes ) )
            throw new \OutOfBoundsException(
                self::$ERROR_column_index_out_of_bounds );

        // Get.
        return $this->rows[$rowIndex][$columnIndex];
    }

    /**
     * Sets the table value at the selected row and column to the given
     * value.
     *
     * Example:
     * @code
     *   $nRows = $table->getNumberOfRows( );
     *   $nCols = $table->getNumberOfColumns( );
     *   for ( $i = 0; $i < $nRows; ++$i )
     *   {
     *     for ( $j = 0; $j < $nCols; ++$j )
     *     {
     *       $table->setValue( $i, $j, 0 );
     *     }
     *   }
     * @endcode
     *
     * @param integer  $rowIndex    the non-negative row index of the
     * row to query.
     *
     * @param integer  $columnIndex the non-negative column index of the
     * column to query.
     *
     * @param mixed    $value       the value to be used to set the
     * table value at the selected row and column.
     *
     * @throws \OutOfBoundsException if $rowIndex or $columnIndex are
     * out of bounds.
     */
    public function setValue( $rowIndex, $columnIndex, $value )
    {
        // Validate.
        if ( $rowIndex < 0 || $rowIndex >= count( $this->rows ) )
            throw new \OutOfBoundsException(
                self::$ERROR_row_index_out_of_bounds );
        if ( $columnIndex < 0 ||
            $columnIndex >= count( $this->columnAttributes ) )
            throw new \OutOfBoundsException(
                self::$ERROR_column_index_out_of_bounds );

        // Set.
        $this->rows[$rowIndex][$columnIndex] = $value;
    }
    // @}
}

/**
 * @file
 * Defines SDSC\StructuredData\Tree to manage a tree with a root node and
 * children that may, in turn, have children.
 */

namespace SDSC\StructuredData;






/**
 * @class Tree
 * Tree manages a named hierarchy of named nodes that each contain a
 * list of named values and metadata.
 *
 * #### Tree attributes
 * Trees have an associative array of attributes that
 * provide descriptive metadata for the data content.  Applications may
 * add any number and type of attributes, but this class, and others in
 * this package, recognize a few well-known attributes:
 *
 *  - 'name' (string) is a brief name of the data
 *  - 'longName' (string) is a longer more human-friendly name of the data
 *  - 'description' (string) is a block of text describing the data
 *  - 'sourceFileName' (string) is the name of the source file for the data
 *  - 'sourceSyntax' (string) is the source file base syntax
 *  - 'sourceMIMEType' (string) is the source file mime type
 *  - 'sourceSchemaName' (string) is the name of a source file schema
 *
 * All attributes are optional.
 *
 * The 'name' may be an abbreviation or acronym, while the 'longName' may
 * spell out the abbreviation or acronym.
 *
 * The 'description' may be a block of text containing several unformatted
 * sentences describing the data.
 *
 * When the data originates from a source file, the 'sourceFileName' may
 * be the name of the file. If that file's syntax does not provide a name
 * for the data, the file's name, without extensions, may be used to set
 * the name.
 *
 * In addition to the source file name, the file's MIME type may be set
 * in 'sourceMIMEType' (e.g. 'application/json'), and the equivalent file
 * syntax in 'sourceSyntax' e.g. 'json'). If the source file uses a specific
 * schema, the name of that schema is in 'sourceSchemaName' (e.g.
 * 'json-tree').
 *
 *
 * #### Node attributes
 * Trees have zero or more nodes. Each node has an associative array
 * of attributes that provide descriptive metadata for the node. Applications
 * may add any number and type of attributes, but this class, and others in
 * this package, recognize a few well-known attributes:
 *
 *  - 'name' (string) is a brief name of the data
 *  - 'longName' (string) is a longer more human-friendly name of the data
 *  - 'description' (string) is a block of text describing the data
 *
 * All attributes are optional.
 *
 * The 'name' may be an abbreviation or acronym, while the 'longName' may
 * spell out the abbreviation or acronym.  The node 'name' is optional
 * but strongly encouraged.  If abscent, classes that format nodes for a
 * specific output syntax (e.g. CSV or JSON) will create numbered node
 * names (e.g. '1', '2', etc.).
 *
 * The 'description' may be a block of text containing several unformatted
 * sentences describing the data.
 *
 *
 * #### Nodes
 * A tree may have zero or more nodes with values. Values have names and
 * any data type.  Value names must be strings.
 *
 * The tree starts with a root node that has an arbitrary number of
 * child nodes. Each of those children may have an arbitrary number
 * of further child nodes, and so on recursively.
 *
 *
 * @author  David R. Nadeau / University of California, San Diego
 *
 * @date    2/8/2016
 *
 * @since   0.0.1  Initial development.
 *
 * @version 0.0.1  Initial development.
 *
 * @version 0.0.2  Revised to generalize tree and node attributes into
 *   associative arrays instead of explicit attributes.
 *
 * @version 0.0.3  Revised to make the node array, children arrays, and
 *   the name map's entries all associative arrays where keys are node IDs.
 *
 * @version 0.0.4  Revised to subclass AbstractData and throw standard
 *   SPL exceptions.
 *
 * @version 0.0.5  Revised to move node values (using attributes instead),
 *   and to move them into a sub-array so that user keys cannot collide
 *   with internal keys for parents and children.
 */
final class Tree
    extends AbstractData
{
//----------------------------------------------------------------------
// Fields
//----------------------------------------------------------------------
    /**
     * @var  object $rootID
     * The root node id. The id is -1 if there is no root.
     */
    private $rootID;



    /**
     * @var  array $nodes
     * An array of nodes with numeric node ID keys and associative
     * array values.  The order of nodes is irrelevant.  Deletion
     * of a node unsets the array entry, causing array keys to *not*
     * be consecutive integers.
     *
     * Validation of a node ID checks if the ID is a valid key for
     * the array.
     *
     * The number of nodes equals count( $nodes ).
     *
     * Each node in the array is an associative array with keys for:
     *      - 'attributes'  - associative array of named attributes
     *      - 'children'    - array of children node IDs as keys
     *      - 'parent'      - parent's node ID
     *
     * The 'attributes' key selects an associative array containing
     * named attributes/values. Well-known attributes include:
     *
     *      - 'name'        - short name
     *      - 'longName'    - long name
     *      - 'description' - description
     *
     * The 'parent' key selects a scalar value that always exists and
     * is initialized to -1 for the root node, and an integer node ID
     * for all other nodes.
     *
     * The 'children' key selects an associative array that always
     * exists and is initially empty.  This array is associative
     * where keys are node IDs for the children, and values are always
     * 0 (they are not used).
     */
    private $nodes;

    /**
     * @var  array $nodeNameMap
     * An associative array with node name string keys. An entry
     * exists if a particular name is used by one or more nodes.
     *
     * Each entry is an associative array where array keys are numeric
     * node IDs, and values are always '0' - the value is not used and
     * is merely there to fill an entry. The array keys are what are
     * used to provide a list of node IDs with the same name.
     */
    private $nodeNameMap;

    /**
     * @var  integer $nextNodeID
     * The next available unique non-negative integer node ID.
     * Node IDs start at 0 for an empty tree, then increment each
     * time a node is added. On deletion, the IDs of deleted nodes
     * are *not* reused. Node IDs are monotonicaly increasing.
     */
    private $nextNodeID;





//----------------------------------------------------------------------
// Constants
//----------------------------------------------------------------------
    /**
     * @var array WELL_KNOWN_NODE_ATTRIBUTES
     * An associative array where the keys are the names of well-known
     * node attributes.
     */
    public static $WELL_KNOWN_NODE_ATTRIBUTES = array(
        'name'        => 1,
        'longName'    => 1,
        'description' => 1
    );

    private static $ERROR_tree_node_id_invalid =
        'Tree node ID is out of bounds.';
    private static $ERROR_node_attributes_invalid =
        'Node attributes must be an array or object.';
    private static $ERROR_node_values_invalid =
        'Node values must be an array or object.';

    private static $ERROR_node_attribute_key_invalid =
        'Node attribute keys must be non-empty strings.';
    private static $ERROR_node_attribute_wellknown_key_value_invalid =
        'Node attribute values for well-known keys must be strings.';





//----------------------------------------------------------------------
// Constructors & Destructors
//----------------------------------------------------------------------
    /**
     * @name Constructors
     */
    // @{
    /**
     * Constructs an empty tree with no nodes and the provided
     * list of attributes, if any.
     *
     *
     * @param   array $attributes  an optional associatve array of named
     * attributes associated with the tree
     *
     * @return  Tree             returns a new empty tree with the
     * provided tree attributes
     *
     * @throws \InvalidArgumentException  if $attributes is not an array,
     * object, or NULL, or if any of its attributes have invalid keys or
     * values.
     */
    public function __construct( $attributes = NULL )
    {
        parent::__construct( $attributes );

        // Initialize empty node arrays.
        $this->nodes       = array( );
        $this->nodeNameMap = array( );
        $this->rootID      = -1;
        $this->nextNodeID  = 0;
    }

    /**
     * Clones the data by doing a deep copy of all attributes and values.
     */
    public function __clone( )
    {
        // For any property that is an object or array, make a
        // deep copy by forcing a serialize, then unserialize.
        foreach ( $this as $key => &$value )
        {
            if ( is_object( $value ) || is_array( $value ) )
                $this->{$key} = unserialize( serialize( $value ) );
        }
    }
    // @}

    /**
     * @name Destructors
     */
    // @{
    /**
     * Destroys the previously constructed tree.
     */
    public function __destruct( )
    {
        parent::__destruct( );
    }
    // @}





//----------------------------------------------------------------------
// Utility methods
//----------------------------------------------------------------------
    /**
     * @name Utility methods
     */
    // @{
    /**
     * Adds the selected node's ID to the name table with the given name.
     *
     * The given node ID is assumed to be valid.
     *
     * @param integer $nodeID  the unique non-negative numeric ID of a node.
     *
     * @param string   $name    a string containing the name name
     * of the node, or an empty string if there is no name name.
     */
    private function _addNodeToNameMap( $nodeID, $name )
    {
        // The $nodeNameMap is an associative array where names are
        // the keys. Entry values are associative arrays where the
        // keys are node IDs, and the values are irrelevant.
        if ( $name === '' || $name === NULL )
            return;

        // If the map has no current entry for the name, add one.
        // Otherwise, add a key for the new node ID. Values are
        // not used and are always 0.
        if ( !isset( $this->nodeNameMap[$name] ) )
            $this->nodeNameMap[$name]          = array( $nodeID => 0 );
        else
            $this->nodeNameMap[$name][$nodeID] = 0;
    }

    /**
     * Removes the selected node's ID from the name table entry with
     * the given name.
     *
     * The given node ID is assumed to be valid.
     *
     * @param integer $nodeID  the unique non-negative numeric ID of a node.
     *
     * @param string   $name    a string containing the name
     * of the node, or an empty string if there is no name.
     */
    private function _deleteNodeFromNameMap( $nodeID, $name )
    {
        // The $nodeNameMap is an associative array where names are
        // the keys. Entry values are associative arrays where the
        // keys are node IDs, and the values are irrelevant.
        if ( $name === '' || $name === NULL )
            return;

        // If the map has no entry for the name, then the name was not
        // in use and we're done.  This should never happen since all
        // nodes with names are added to the name map.
        //
        // Since this should never happen, there is no way to test this.
        // @codeCoverageIgnoreStart
        if ( !isset( $this->nodeNameMap[$name] ) )
            return;                         // Name is not in use
        // @codeCoverageIgnoreEnd

        // If the map entry has no key for the node, then the node was
        // not registered as using this name and we're done.  Again,
        // this should never happen since all entries have nodes.
        //
        // Since this should never happen, there is no way to test this.
        // @codeCoverageIgnoreStart
        if ( !isset( $this->nodeNameMap[$name][$nodeID] ) )
            return;                         // Node isn't registered for name
        // @codeCoverageIgnoreEnd

        // Unset the map entry's key for the node.
        unset( $this->nodeNameMap[$name][$nodeID] );

        // If that makes the map entry empty, unset it.
        if ( empty( $this->nodeNameMap[$name] ) )
            unset( $this->nodeNameMap[$name] );
    }

    /**
     * Recursively deletes the selected node and all of its children.
     *
     * The given node ID, and all of the children node IDs, are assumed
     * to be valid.
     *
     * @param integer $nodeID  the unique non-negative numeric ID of a node.
     */
    private function _recursiveDeleteNode( $nodeID )
    {
        // Save the node's name and list of children.
        $name = NULL;
        if ( isset( $this->nodes[$nodeID]['attributes']['name'] ) )
            $name = $this->nodes[$nodeID]['attributes']['name'];

        $children = array( );
        if ( isset( $this->nodes[$nodeID]['children'] ) )
            $children = $this->nodes[$nodeID]['children'];

        // Delete the node from the node table.
        unset( $this->nodes[$nodeID] );

        // Delete the node from the associative array of node names.
        $this->_deleteNodeFromNameMap( $nodeID, $name );

        // Recurse to delete all of the node's children.  Keys for the
        // children array are node IDs, while values are not used.
        foreach ( $children as $childID => $unusedValue )
            $this->_recursiveDeleteNode( $childID );
    }

    /**
     * Validates a nodeID and throws an exception if the ID is out of
     * range.
     *
     * @param integer $nodeID  the unique non-negative numeric ID of a node.
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     */
    private function _validateNodeID( $nodeID )
    {
        // The $nodes array is an associative array where node IDs are
        // the keys. IDs are always non-negative. If an ID is negative
        // or if there is no entry for the ID, then the ID is not valid.
        if ( $nodeID < 0 || !isset( $this->nodes[$nodeID] ) )
            throw new \OutOfBoundsException(
                self::$ERROR_tree_node_id_invalid );
    }
    // @}





//----------------------------------------------------------------------
// Node attributes methods
//----------------------------------------------------------------------
    /**
     * @name Node attributes methods
     */
    // @{
    /**
     * Clears attributes for the selected node, while retaining its
     * links to its parent and children, if any.
     *
     * Example:
     * @code
     *   $tree->clearNodeAttributes( $id );
     * @endcode
     *
     * @param  integer $nodeID  the unique non-negative numeric ID of the node.
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     */
    public function clearNodeAttributes( $nodeID )
    {
        // Validate the node ID.
        $this->_validateNodeID( $nodeID );

        // Clear attributes. If there is a node name, remove the node
        // from the name map.
        if ( !isset( $this->nodes[$nodeID]['attributes']['name'] ) )
            $this->nodes[$nodeID]['attributes'] = array( );
        else
        {
            $name = $this->nodes[$nodeID]['attributes']['name'];
            $this->nodes[$nodeID]['attributes'] = array( );
            $this->_deleteNodeFromNameMap( $nodeID, $name );
        }
    }

    /**
     * Returns an array of node IDs for nodes with the selected
     * name, or an empty array if there are no nodes with the name.
     *
     * Example:
     * @code
     *   $ids = $tree->findNodesByName( 'abc' );
     *   foreach ( $ids as $id )
     *   {
     *     print( "Node $id\n" );
     *   }
     * @endcode
     *
     * @return  array  returns an array of node IDs for nodes with
     * the given name, or an empty array if no nodes were found.
     *
     * @throws \InvalidArgumentException  if $name is not a non-empty string.
     */
    public function findNodesByName( $name )
    {
        // Validate.
        if ( !is_string( $name ) || $name === '' )
            throw new \InvalidArgumentException(
                self::$ERROR_node_attribute_key_invalid );

        // The name map is an associative array where the keys are names
        // and the values are arrays. Those arrays are each associative
        // where the keys are node IDs and the values are unused.

        // If the map has no entry for the name, there are no nodes with
        // that name.
        if ( !isset( $this->nodeNameMap[$name] ) )
            return array( );

        // Otherwise return the keys for that name's array. These are
        // node IDs.
        return array_keys( $this->nodeNameMap[$name] );
    }

    /**
     * Returns a copy of the selected attribute for the selected node,
     * or a NULL if the attribute does not exist.
     *
     * Example:
     * @code
     *   $tree->getNodeAttribute( $id, 'name' );
     * @endcode
     *
     * @param  integer $nodeID  the unique non-negative numeric ID of the node.
     *
     * @param  string  $key     the name of an attribute to query
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     *
     * @throws \InvalidArgumentException  if $key is not a non-empty string.
     */
    public function getNodeAttribute( $nodeID, $key )
    {
        // Validate the node ID.
        $this->_validateNodeID( $nodeID );
        if ( !is_string( $key ) || $key === '' )
            throw new \InvalidArgumentException(
                self::$ERROR_node_attribute_key_invalid );

        // Get.
        if ( isset( $this->nodes[$nodeID]['attributes'][$key] ) )
            return $this->nodes[$nodeID]['attributes'][$key];
        return NULL;                        // No such key
    }

    /**
     * Returns a copy of all attributes for the selected node.
     *
     * Example:
     * @code
     *   $tree->getNodeAttributes( $id );
     * @endcode
     *
     * @param  integer $nodeID  the unique non-negative numeric ID of the node.
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     */
    public function getNodeAttributes( $nodeID )
    {
        // Validate the node ID.
        $this->_validateNodeID( $nodeID );

        // Get.
        return $this->nodes[$nodeID]['attributes'];
    }

    /**
     * Returns a "best" node name by checking for, in order, the long name
     * and short name, and returning the first non-empty value
     * found, or the node id if all of those are empty.
     *
     * Example:
     * @code
     *   $bestName = $data->getNodeBestName( $id );
     * @endcode
     *
     * This method is a convenience function that is the equivalent of
     * checking each of the long name and name attributes in order.
     *
     * @param  integer $nodeID  the unique non-negative numeric ID of the node.
     *
     * @return  the best name
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     */
    public function getNodeBestName( $nodeID )
    {
        $v = $this->getNodeAttribute( $nodeID, 'longName' );
        if ( $v !== '' && $v !== NULL )
            return strval( $v );
        $v = $this->getNodeAttribute( $nodeID, 'name' );
        if ( $v !== '' && $v !== NULL )
            return strval( $v );
        return strval( $nodeID );
    }

    /**
     * Returns the description of the selected node, or an empty string if it
     * has no description.
     *
     * Example:
     * @code
     *   $description = $tree->getNodeDescription( $id );
     * @endcode
     *
     * @param  integer $nodeID  the unique non-negative numeric ID of the node.
     *
     * @return string  the description for the selected node, or an empty
     * string if the node has no description.
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     */
    public function getNodeDescription( $nodeID )
    {
        $v = $this->getNodeAttribute( $nodeID, 'description' );
        if ( $v !== '' && $v !== NULL )
            return strval( $v );
        return '';
    }

    /**
     * Returns the long name of the selected node, or an empty string if it
     * has no long name.
     *
     * Example:
     * @code
     *   $longName = $tree->getNodeLongName( $id );
     * @endcode
     *
     * @param  integer $nodeID  the unique non-negative numeric ID of the node.
     *
     * @return string  the long name for the selected node, or an empty
     * string if the node has no long name.
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     */
    public function getNodeLongName( $nodeID )
    {
        $v = $this->getNodeAttribute( $nodeID, 'longName' );
        if ( $v !== '' && $v !== NULL )
            return strval( $v );
        return '';
    }

    /**
     * Returns the name of the selected node, or an empty string if it
     * has no name.
     *
     * Example:
     * @code
     *   $name = $tree->getNodeName( $id );
     * @endcode
     *
     * @param  integer $nodeID  the unique non-negative numeric ID of the node.
     *
     * @return string  the name for the selected node, or an empty string if
     * the node has no name.
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     */
    public function getNodeName( $nodeID )
    {
        $v = $this->getNodeAttribute( $nodeID, 'name' );
        if ( $v !== '' && $v !== NULL )
            return strval( $v );
        return '';
    }

    /**
     * Returns an array of keywords found in the node's attributes,
     * including the name, long name, description, and other attributes.
     *
     * Such a keyword list is useful when building a search index to
     * find this data object. The returns keywords array is in
     * lower case, with duplicate words removed, and the array sorted
     * in a natural sort order.
     *
     * The keyword list is formed by extracting all space or punctuation
     * delimited words found in all attribute keys and values. This
     * includes the name, long name, and description attributes, and
     * any others added by the application. Well known attribute
     * names, such as 'name', 'longName', etc., are not included, but
     * application-specific attribute names are included.
     *
     * Numbers and punctuation are ignored. Array and object attribute
     * values are converted to text and then scanned for words.
     *
     * @param  integer $nodeID  the unique non-negative numeric ID of the node.
     *
     * @return array  returns an array of keywords.
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     */
    public function getNodeKeywords( $nodeID )
    {
        // Add all node attribute keys and values for one node.
        $text = '';
        foreach ( $this->nodes[$nodeID]['attributes'] as $key => &$value )
        {
            // Add the key. Skip well-known key names.  Intelligently
            // convert to text.
            if ( !isset( self::$WELL_KNOWN_NODE_ATTRIBUTES[$key] ) )
                $text .= ' ' . $this->valueToText( $key );

            // Add the value.  Intelligently convert to text.
            $text .= ' ' . $this->valueToText( $value );
        }

        // Clean the text of numbers and punctuation, and return
        // an array of keywords.
        return $this->textToKeywords( $text );
    }

    /**
     * Returns an array of keywords found in all node attributes,
     * including the names, long names, descriptions, and other attributes.
     *
     * Such a keyword list is useful when building a search index to
     * find this data object. The returns keywords array is in
     * lower case, with duplicate words removed, and the array sorted
     * in a natural sort order.
     *
     * The keyword list is formed by extracting all space or punctuation
     * delimited words found in all attribute keys and values. This
     * includes the name, long name, and description attributes, and
     * any others added by the application. Well known attribute
     * names, such as 'name', 'longName', etc., are not included, but
     * application-specific attribute names are included.
     *
     * Numbers and punctuation are ignored. Array and object attribute
     * values are converted to text and then scanned for words.
     *
     * @return array  returns an array of keywords.
     */
    public function getAllNodeKeywords( )
    {
        // Add all node attribute keys and values for all nodes.
        $text = '';
        foreach ( $this->nodes as &$node )
        {
            foreach ( $node['attributes'] as $key => &$value )
            {
                // Add the key. Skip well-known key names.  Intelligently
                // convert to text.
                if ( !isset( self::$WELL_KNOWN_NODE_ATTRIBUTES[$key] ) )
                    $text .= ' ' . $this->valueToText( $key );

                // Add the value.  Intelligently convert to text.
                $text .= ' ' . $this->valueToText( $value );
            }
        }

        // Clean the text of numbers and punctuation, and return
        // an array of keywords.
        return $this->textToKeywords( $text );
    }

    /**
     * Merges the given named attribute with the selected node's
     * existing attributes.
     *
     * New attributes overwrite existing attributes with the same name.
     *
     * Example:
     * @code
     *   $table->setNodeAttribute( $id, 'name', 'Total' );
     * @endcode
     *
     * @param integer $nodeID  the non-negative numeric index of the node.
     *
     * @param string  $key  the key of a node attribute.
     *
     * @param mixed   $value  the value of a node attribute.
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     *
     * @throws \InvalidArgumentException  if $key is not a string or is empty,
     * or if $value is not a string when $key is one of the well-known
     * attributes.
     */
    public function setNodeAttribute( $nodeID, $key, $value )
    {
        // Validate. Insure the key is a string, and the value for
        // well-known attributes is a string.
        $this->_validateNodeID( $nodeID );

        if ( !is_string( $key ) || $key === '' )
            throw new \InvalidArgumentException(
                self::$ERROR_node_attribute_key_invalid );

        if ( isset( self::$WELL_KNOWN_NODE_ATTRIBUTES[$key] ) &&
            !is_string( $value ) )
            throw new \InvalidArgumentException(
                self::$ERROR_node_attribute_wellknown_key_value_invalid );

        // Set. If the name changes, remove the old entry from the
        // name map and add the new name, if any.
        if ( (string)$key == 'name' )
        {
            $oldName = $this->nodes[$nodeID]['attributes']['name'];
            $this->_deleteNodeFromNameMap( $nodeID, $oldName );
            $this->nodes[$nodeID]['attributes']['name'] = $value;
            $this->_addNodeToNameMap( $nodeID, 'name' );
        }
        else
            $this->nodes[$nodeID]['attributes'][$key] = $value;
    }

    /**
     * Merges the given associative array of named attributes with the
     * selected node's existing attributes, if any.
     *
     * New attributes overwrite existing attributes with the same name.
     *
     * The node's attributes array may contain additional application-
     * or file format-specific attributes.
     *
     * Example:
     * @code
     *   $attributes = array( 'name' => 'Total' );
     *   $table->setNodeAttributes( $id, $attributes );
     * @endcode
     *
     * @param  integer $nodeID  the unique non-negative numeric ID of the node.
     *
     * @param   array $attributes  an associatve array of named
     * attributes associated with the node.
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     *
     * @throws \InvalidArgumentException  if $attributes is not an array,
     * object, or NULL, or if any of its attributes have invalid keys or
     * values.
     */
    public function setNodeAttributes( $nodeID, $attributes )
    {
        // Validate
        $this->_validateNodeID( $nodeID );
        if ( $attributes == NULL )
            return;
        if ( !is_array( $attributes ) && !is_object( $attributes ) )
            throw new \InvalidArgumentException(
                self::$ERROR_node_attributes_invalid );

        // Convert object argument to an array, if needed.
        $a = (array)$attributes;
        if ( empty( $a ) )
            return;                     // Request to set with nothing

        // Insure keys are all strings and all well-known key values
        // are strings.
        foreach ( $a as $key => $value )
        {
            if ( !is_string( $key ) || $key === '' )
                throw new \InvalidArgumentException(
                    self::$ERROR_node_attribute_key_invalid );

            if ( isset( self::$WELL_KNOWN_NODE_ATTRIBUTES[$key] ) &&
                !is_string( $value ) )
                throw new \InvalidArgumentException(
                    self::$ERROR_node_attribute_wellknown_key_value_invalid );
        }

        // Get the old name, if any.
        if ( isset( $this->nodes[$nodeID]['attributes']['name'] ) )
            $oldName = $this->nodes[$nodeID]['attributes']['name'];
        else
            $oldName = NULL;

        // Set attributes.
        $this->nodes[$nodeID]['attributes'] =
            array_merge( $this->nodes[$nodeID]['attributes'], $a );

        // If the name changed, update the node-to-ID map.
        $newName = $this->nodes[$nodeID]['attributes']['name'];
        if ( $oldName != $newName )
        {
            $this->_deleteNodeFromNameMap( $nodeID, $oldName );
            $this->_addNodeToNameMap( $nodeID, $newName );
        }
    }
    // @}





//----------------------------------------------------------------------
// Node operations
//----------------------------------------------------------------------
    /**
     * @name Node operations
     */
    // @{
    /**
     * Adds a new node as a child of the selected node, initialized
     * with the given attributes and values.
     *
     * Example:
     * @code
     *   $nodeID = $tree->addNode( $parentNodeID, $attributes );
     * @endcode
     *
     * @param integer  $parentNodeID  the unique non-negative numeric ID of
     * the parent node.
     *
     * @param array    $attributes    an associative array of named attributes
     * for the node, or an empty array or NULL if there are no attributes.
     *
     * @return integer               the unique non-negative numeric ID of
     * the new node.
     *
     * @throws \OutOfBoundsException  if $parentNodeID is out of bounds.
     *
     * @throws \InvalidArgumentException  if $attributes is not an array,
     * object, or NULL.
     */
    public function addNode( $parentNodeID, $attributes = NULL )
    {
        // Validate the parent node ID.
        $this->_validateNodeID( $parentNodeID );

        // Validate the attributes and values arrays.
        if ( !is_array( $attributes ) && !is_object( $attributes ) &&
            $attributes != NULL )
            throw new \InvalidArgumentException(
                self::$ERROR_node_attributes_invalid );

        // Create a node entry with attributes, values, a parent,
        // and no children. A node entry is an associative array containing
        // a few specific internal attributes (values, parent, children)
        // and an arbitrary list of well-known and application-specific
        // attributes.
        $node = array( );
        $node['parent']   = $parentNodeID;
        $node['children'] = array( );
        if ( empty( $attributes ) )
            $node['attributes'] = array( );
        else
            $node['attributes'] = (array)$attributes;

        // Use the next available node ID and add the node to the
        // nodes array using that node ID.
        $nodeID = $this->nextNodeID;
        ++$this->nextNodeID;
        $this->nodes[$nodeID] = $node;

        // Add to the parent.  The 'children' array is associative where
        // the keys are node IDs and the values are 0 and unused.
        $this->nodes[$parentNodeID]['children'][$nodeID] = 0;

        // Add to the name-to-ID table.
        if ( isset( $node['attributes']['name'] ) )
            $this->_addNodeToNameMap( $nodeID, $node['attributes']['name'] );

        return $nodeID;
    }

    /**
     * Clears the entire tree, removing all nodes and tree attributes,
     * leaving an entirely empty tree.
     *
     * This method is equivalent to clearing all tree attributes, then
     * deleting all nodes:
     * @code
     *   $tree->clearAttributes( );
     *   $tree->deleteNodes( 0, $tree->getNumberOfNodes( ) );
     * @endcode
     *
     * Example:
     * @code
     *   $tree->clear( );
     * @endcode
     *
     * @see clearAttributes( ) to clear tree attributes while retaining nodes.
     *
     * @see deleteNodes( ) to delete nodes in the tree, while
     *   retaining tree attributes.
     */
    public function clear( )
    {
        // Initialize all arrays to be empty.
        $this->clearAttributes( );
        $this->nodes       = array( );  // Delete nodes
        $this->nodeNameMap = array( );
        $this->rootID      = -1;
        $this->nextNodeID  = 0;
    }

    /**
     * Deletes a selected node and all of its children nodes, if any.
     *
     * If the selected node is the root node of the tree, the entire
     * tree is deleted.
     *
     * Example:
     * @code
     *   $tree->deleteNode( $nodeID );
     * @endcode
     *
     * @param integer $nodeID  the unique non-negative numeric ID of a node.
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     */
    public function deleteNode( $nodeID )
    {
        // Validate the node ID.
        $this->_validateNodeID( $nodeID );

        if ( $nodeID == $this->rootID )
        {
            // Node to delete is root. Clear entire tree.
            $this->nodes       = array( );
            $this->nodeNameMap = array( );
            $this->rootID      = -1;
            $this->nextNodeID  = 0;
        }
        else
        {
            // Recursively delete the subtree at the node.
            $parentID = $this->nodes[$nodeID]['parent'];
            $this->_recursiveDeleteNode( $nodeID );

            // Get the parent's list of children. The array is associative
            // where keys are children node IDs and values are unused.
            // To delete the child, unset the entry for the child's node ID.
            unset( $this->nodes[$parentID]['children'][$nodeID] );
        }
    }

    /**
     * Returns the node IDs of all nodes in the tree, or an empty array
     * if the tree is empty.
     *
     * Example:
     * @code
     *   $nodes = $tree->getAllNodes( );
     *   foreach ( $nodes as $nodeID )
     *   {
     *     $name = $tree->getNodeName( $nodeID );
     *     print( "Node $nodeID = $name\n" );
     *   }
     * @endcode
     *
     * @return array  an array of unique non-negative numeric IDs for the
     * nodes in the tree.
     */
    public function getAllNodes( )
    {
        // The $nodes array is associative where the keys are node IDs.
        // Return an array of those keys.
        return array_keys( $this->nodes );
    }

    /**
     * Returns a array of node IDs for direct children of the node, or an
     * empty array if the node has no children.
     *
     * Example:
     * @code
     *   $children = $tree->getNodeChildren( $nodeID );
     *   foreach ( $children as $id )
     *   {
     *     print( "Node $id\n" );
     *   }
     * @endcode
     *
     * @param  integer $nodeID  the unique non-negative numeric ID of the node
     *
     * @return array  returns an array of node IDs of children of
     * the node.
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     */
    public function getNodeChildren( $nodeID )
    {
        // Validate the node ID.
        $this->_validateNodeID( $nodeID );

        // The node's 'children' array is associative where the keys are
        // node IDs and the values are unused. Return an array of the keys.
        return array_keys( $this->nodes[$nodeID]['children'] );
    }

    /**
     * Returns the unique non-negative numeric ID of the parent node
     * of the selected node, or a -1 if the node is the root node
     * of the tree.
     *
     * Example:
     * @code
     *   $parentID = $tree->getNodeParent( $nodeID );
     * @endcode
     *
     * @param  integer $nodeID  the unique non-negative numeric ID of the node
     *
     * @return integer          the unique non-negative numeric ID of the
     * parent node of the selected node.
     *
     * @throws \OutOfBoundsException  if $nodeID is out of bounds.
     */
    public function getNodeParent( $nodeID )
    {
        // Validate the node ID.
        $this->_validateNodeID( $nodeID );

        return $this->nodes[$nodeID]['parent'];
    }

    /**
     * Returns the depth of the tree.
     *
     * If the tree has no root node, the depth is 0.
     *
     * If there is only a root node, the depth is 1.
     *
     * Otherwise, the number of levels is the maximum depth to any node,
     * found by searching the tree.
     *
     * Example:
     * @code
     *   $levels = $tree->getDepth( );
     * @endcode
     *
     * @return  integer returns the depth of the tree.
     */
    public function getDepth( )
    {
        if ( $this->rootID == -1 )
            return 0;
        return $this->findMaxDepth( $this->rootID );
    }

    /**
     * Recursively searches the tree, starting at the given node, to find
     * the maximum depth from that node downward.
     *
     * If the node has no children, the return depth is 1.  Otherwise it
     * is the maximum of the depths for all subtrees rooted at the
     * node's children.
     *
     * @param   integer $nodeID the unique non-negative numeric ID of the
     * node to start the search on.
     *
     * @return  integer         the maximum depth of the tree, starting at
     * the gien node.
     */
    private function findMaxDepth( $nodeID )
    {
        // Get the node's children.
        $children = array_keys( $this->nodes[$nodeID]['children'] );
        $max = 1;
        foreach ( $children as $childID )
        {
            $d = 1 + $this->findMaxDepth( $childID );
            if ( $d > $max )
                $max = $d;
        }
        return $max;
    }

    /**
     * Returns the total number of nodes in the tree.
     *
     * Example:
     * @code
     *   $number = $tree->getNumberOfNodes( );
     * @endcode
     *
     * @return integer  returns the number of nodes in the tree.
     */
    public function getNumberOfNodes( )
    {
        return count( $this->nodes );
    }

    /**
     * Returns the unique ID of the root node.
     *
     * The node ID may be used with getNode( ) to return attributes
     * and values for the node, and a list of the node's children.
     *
     * Example:
     * @code
     *   $rootID = $tree->getRootID( );
     *   $rootName = $tree->getNodeShortName( $rootID );
     * @endcode
     *
     * @return  integer  returns a unique integer ID for the root node,
     * or a -1 if there is no root node.
     */
    public function getRootNodeID( )
    {
        return $this->rootID;
    }

    /**
     * Sets the root node, initializing it with the given attributes
     * and values.
     *
     * If the tree already has a root node, the tree is cleared first,
     * deleting all of its nodes.
     *
     * Example:
     * @code
     *   $rootID = $tree->setRootNode( $attributes );
     * @endcode
     *
     * @param array    $attributes    an associative array of named attributes
     * for the node, or an empty array or NULL if there are no attributes.
     *
     * @return integer               the unique non-negative numeric ID of
     * the new root node.
     *
     * @throws \InvalidArgumentException  if $attributes is not an array,
     * object, or NULL.
     */
    public function setRootNode( $attributes = NULL )
    {
        // Validate attributes and values arrays.
        if ( !is_array( $attributes ) && !is_object( $attributes ) &&
            $attributes != NULL )
            throw new \InvalidArgumentException(
                self::$ERROR_node_attributes_invalid );

        // Delete the current tree's nodes first.
        if ( $this->rootID != -1 )
        {
            $this->nodes       = array( );      // Delete nodes
            $this->nodeNameMap = array( );
            $this->rootID      = -1;
            $this->nextNodeID  = 0;
        }

        // Create a node entry with attributes, values, a parent,
        // and no children. A node entry is an associative array containing
        // a few specific internal attributes (values, parent, children)
        // and an arbitrary list of well-known and application-specific
        // attributes.
        $node = array( );
        $node['parent']   = -1;                 // Root has no parent
        $node['children'] = array( );
        if ( empty( $attributes ) )
            $node['attributes'] = array( );
        else
            $node['attributes'] = $attributes;

        // Use the next available node ID and add the node to the
        // nodes array using that node ID.
        $this->rootID = $this->nextNodeID;
        ++$this->nextNodeID;
        $this->nodes[$this->rootID] = $node;

        // Add to the name-to-ID table.
        if ( isset( $node['attributes']['name'] ) )
            $this->_addNodeToNameMap( $this->rootID, $node['attributes']['name'] );

        return $this->rootID;
    }
    // @}
}
