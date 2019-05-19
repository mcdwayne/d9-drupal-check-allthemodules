<?xml version="1.0" encoding="ASCII"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:exsl="http://exslt.org/common" xmlns:cf="http://docbook.sourceforge.net/xmlns/chunkfast/1.0" xmlns:ng="http://docbook.org/docbook-ng" xmlns:db="http://docbook.org/ns/docbook" xmlns="http://www.w3.org/1999/xhtml" version="1.0" exclude-result-prefixes="exsl cf ng db">

<!-- This file contains overrides for output for ePub e-books. -->
<!-- Note that epub is normally made with the epub stylesheets, from the
     docbook-xsl project. -->

<!-- No title on the abstract for title page. -->
<xsl:param name="abstract.notitle.enabled" select="1"/>

<!-- Output the language and direction in the HTML head. -->
<xsl:template name="root.attributes">
  <xsl:variable name="lang">
    <xsl:call-template name="l10n.language"/>
  </xsl:variable>
  <xsl:if test="starts-with($lang, 'he') or
                starts-with($lang, 'ar') or
                starts-with($lang, 'fa')"
          >
    <xsl:attribute name="dir">rtl</xsl:attribute>
  </xsl:if>
  <xsl:attribute name="lang">$lang</xsl:attribute>
</xsl:template>

<!-- Use outline numbering for sections. -->
<xsl:param name="section.autolabel" select="1"/>
<xsl:param name="section.autolabel.max.depth">1</xsl:param>
<xsl:param name="section.label.includes.component.label" select="1"/>
<xsl:param name="preface.autolabel">i</xsl:param>

<!-- Set the maximum depth for tables of contents to 2. -->
<xsl:variable name="toc.max.depth">
  <xsl:value-of select="'2'" />
</xsl:variable>

<!-- Omit output for paragraphs whose role is "summary". -->
<xsl:template match="simpara[@role='summary']">
</xsl:template>

<!-- Override index term output, to change the link title. -->
<xsl:template match="indexterm" mode="reference">
  <xsl:param name="scope" select="."/>
  <xsl:param name="role" select="''"/>
  <xsl:param name="type" select="''"/>
  <xsl:param name="position"/>
  <xsl:param name="separator" select="''"/>

  <xsl:variable name="term.separator">
    <xsl:call-template name="index.separator">
      <xsl:with-param name="key" select="'index.term.separator'"/>
    </xsl:call-template>
  </xsl:variable>

  <xsl:variable name="number.separator">
    <xsl:call-template name="index.separator">
      <xsl:with-param name="key" select="'index.number.separator'"/>
    </xsl:call-template>
  </xsl:variable>

  <xsl:variable name="range.separator">
    <xsl:call-template name="index.separator">
      <xsl:with-param name="key" select="'index.range.separator'"/>
    </xsl:call-template>
  </xsl:variable>

  <xsl:choose>
    <xsl:when test="$separator != ''">
      <xsl:value-of select="$separator"/>
    </xsl:when>
    <xsl:when test="$position = 1">
      <xsl:value-of select="$term.separator"/>
    </xsl:when>
    <xsl:otherwise>
      <xsl:value-of select="$number.separator"/>
    </xsl:otherwise>
  </xsl:choose>

  <!-- This line is added. -->
  <xsl:variable name="insection" select="ancestor-or-self::section" />

  <xsl:choose>
    <xsl:when test="@zone and string(@zone)">
      <xsl:call-template name="reference">
        <xsl:with-param name="zones" select="normalize-space(@zone)"/>
        <xsl:with-param name="position" select="position()"/>
        <xsl:with-param name="scope" select="$scope"/>
        <xsl:with-param name="role" select="$role"/>
        <xsl:with-param name="type" select="$type"/>
      </xsl:call-template>
    </xsl:when>
    <xsl:otherwise>
      <a>
        <xsl:apply-templates select="." mode="class.attribute"/>
        <!-- This is a changed part! Will only work for AsciiDoc output. -->
        <xsl:variable name="title">
          <xsl:choose>
            <xsl:when test="count($insection) &gt; 0">
              <xsl:apply-templates select="ancestor-or-self::section[last()]" mode="title.markup"/>
            </xsl:when>
            <xsl:otherwise>
              <xsl:apply-templates select="(ancestor-or-self::glossary|ancestor-or-self::chapter|ancestor-or-self::preface|ancestor-or-self::appendix|ancestor-or-self::part|ancestor-or-self::book)[last()]" mode="title.markup"/>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:variable>

        <!-- This is a changed part! Will only work for AsciiDoc output. -->
        <xsl:attribute name="href">
          <xsl:choose>
            <xsl:when test="count($insection) &gt; 0">
              <xsl:call-template name="href.target">
                <xsl:with-param name="object" select="ancestor-or-self::section[last()]"/>
                <xsl:with-param name="context" select="(//index[count(ancestor::node()|$scope) = count(ancestor::node()) and ($role = @role or $type = @type or (string-length($role) = 0 and string-length($type) = 0))] | //setindex[count(ancestor::node()|$scope) = count(ancestor::node()) and ($role = @role or $type = @type or (string-length($role) = 0 and string-length($type) = 0))])[1]"/>
              </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
              <xsl:call-template name="href.target">
                <xsl:with-param name="object" select="(ancestor-or-self::glossary|ancestor-or-self::chapter|ancestor-or-self::preface|ancestor-or-self::appendix|ancestor-or-self::part|ancestor-or-self::book)[last()]"/>
                <xsl:with-param name="context" select="(//index[count(ancestor::node()|$scope) = count(ancestor::node()) and ($role = @role or $type = @type or (string-length($role) = 0 and string-length($type) = 0))] | //setindex[count(ancestor::node()|$scope) = count(ancestor::node()) and ($role = @role or $type = @type or (string-length($role) = 0 and string-length($type) = 0))])[1]"/>
              </xsl:call-template>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:attribute>

        <xsl:value-of select="$title"/> <!-- text only -->
      </a>

      <xsl:variable name="id" select="(@id|@xml:id)[1]"/>
      <xsl:if test="key('endofrange', $id)[count(ancestor::node()|$scope) = count(ancestor::node()) and ($role = @role or $type = @type or (string-length($role) = 0 and string-length($type) = 0))]">
        <xsl:apply-templates select="key('endofrange', $id)[count(ancestor::node()|$scope) = count(ancestor::node()) and ($role = @role or $type = @type or (string-length($role) = 0 and string-length($type) = 0))][last()]" mode="reference">
          <xsl:with-param name="position" select="position()"/>
          <xsl:with-param name="scope" select="$scope"/>
          <xsl:with-param name="role" select="$role"/>
          <xsl:with-param name="type" select="$type"/>
          <xsl:with-param name="separator" select="$range.separator"/>
        </xsl:apply-templates>
      </xsl:if>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

</xsl:stylesheet>
