<?xml version="1.0" encoding="ASCII"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:fo="http://www.w3.org/1999/XSL/Format"
                xmlns:exsl="http://exslt.org/common"
                xmlns:cf="http://docbook.sourceforge.net/xmlns/chunkfast/1.0"
                xmlns:ng="http://docbook.org/docbook-ng"
                xmlns:db="http://docbook.org/ns/docbook"
                xmlns="http://www.w3.org/1999/xhtml" version="1.0"
                exclude-result-prefixes="exsl cf ng db">

<!-- This file contains overrides for output for PDF e-books using
   the BabelStone Han font (a CJK font)-->
<!-- Note that PDF is normally made with the fo stylesheets
     from the docbook-xsl project. -->

<!-- No title on the abstract for title page. -->
<xsl:param name="abstract.notitle.enabled" select="1"/>

<!-- Use FOP extensions, so RTL languages are supported. -->
<xsl:param name="fop1.extensions" select="1"/>

<!-- Strip unnecessary whitespace. -->
<xsl:strip-space elements="*"/>

<!-- Use outline numbering for sections. -->
<xsl:param name="section.autolabel" select="1"/>
<xsl:param name="section.autolabel.max.depth">1</xsl:param>
<xsl:param name="section.label.includes.component.label" select="1"/>
<xsl:param name="preface.autolabel">i</xsl:param>

<!-- Set the maximum depth for tables of contents to 2. -->
<xsl:variable name="toc.max.depth">
  <xsl:value-of select="'2'" />
</xsl:variable>

<!-- Waste less space by not indenting all text. -->
<xsl:param name="body.start.indent">0pt</xsl:param>

<!-- Do not show the URL after links -->
<xsl:param name="ulink.show" select="0"></xsl:param>

<!-- Better link properties. -->
<xsl:attribute-set name="xref.properties">
  <xsl:attribute name="font-style">italic</xsl:attribute>
  <xsl:attribute name="text-decoration">underline</xsl:attribute>
  <xsl:attribute name="color">blue</xsl:attribute>
</xsl:attribute-set>

<!-- Images -->
<xsl:param name="default.image.width">12cm</xsl:param>

<!-- Title page with cover image -->
<xsl:template name="book.titlepage.recto">
  <xsl:choose>
    <xsl:when test="bookinfo/title">
      <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/title"/>
    </xsl:when>
    <xsl:when test="info/title">
      <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="info/title"/>
    </xsl:when>
    <xsl:when test="title">
      <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="title"/>
    </xsl:when>
  </xsl:choose>

  <xsl:choose>
    <xsl:when test="bookinfo/subtitle">
      <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/subtitle"/>
    </xsl:when>
    <xsl:when test="info/subtitle">
      <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="info/subtitle"/>
    </xsl:when>
    <xsl:when test="subtitle">
      <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="subtitle"/>
    </xsl:when>
  </xsl:choose>

  <xsl:if test="bookinfo/mediaobject">
    <xsl:apply-templates select="bookinfo/mediaobject" />
  </xsl:if>

  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/corpauthor"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="info/corpauthor"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/authorgroup"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="info/authorgroup"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/author"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="info/author"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/itermset"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="info/itermset"/>
</xsl:template>

<!-- Omit output for paragraphs whose role is "summary". -->
<xsl:template match="simpara[@role='summary']">
</xsl:template>


<!-- Better fonts. Less whitespace. -->
<xsl:param name="body.font.family">BabelStone Han</xsl:param>
<xsl:param name="body.font.master">11</xsl:param>
<xsl:param name="title.font.family">BabelStone Han</xsl:param>
<xsl:attribute-set name="section.title.level1.properties">
  <xsl:attribute name="font-size">
    <xsl:value-of select="$body.font.master * 1.8"/>
    <xsl:text>pt</xsl:text>
  </xsl:attribute>
  <xsl:attribute name="space-before.minimum">1.8em</xsl:attribute>
  <xsl:attribute name="space-before.optimum">2em</xsl:attribute>
  <xsl:attribute name="space-before.maximum">2.2em</xsl:attribute>
  <xsl:attribute name="space-after.minimum">0.8em</xsl:attribute>
  <xsl:attribute name="space-after.optimum">1em</xsl:attribute>
  <xsl:attribute name="space-after.maximum">1.2em</xsl:attribute>
</xsl:attribute-set>
<xsl:attribute-set name="section.title.level2.properties">
  <xsl:attribute name="font-size">
    <xsl:value-of select="$body.font.master * 1.4"/>
    <xsl:text>pt</xsl:text>
  </xsl:attribute>
  <xsl:attribute name="space-after.minimum">0.8em</xsl:attribute>
  <xsl:attribute name="space-after.optimum">1em</xsl:attribute>
  <xsl:attribute name="space-after.maximum">1.2em</xsl:attribute>
</xsl:attribute-set>
<xsl:attribute-set name="section.title.level3.properties">
  <xsl:attribute name="font-size">
    <xsl:value-of select="$body.font.master * 1.2"/>
    <xsl:text>pt</xsl:text>
  </xsl:attribute>
  <xsl:attribute name="space-after.minimum">0.8em</xsl:attribute>
  <xsl:attribute name="space-after.optimum">1em</xsl:attribute>
  <xsl:attribute name="space-after.maximum">1.2em</xsl:attribute>
</xsl:attribute-set>
<xsl:attribute-set name="section.title.level4.properties">
  <xsl:attribute name="font-size">
    <xsl:value-of select="$body.font.master * 1.1"/>
    <xsl:text>pt</xsl:text>
  </xsl:attribute>
</xsl:attribute-set>
<xsl:param name="header.column.widths">1 5 1</xsl:param>
<xsl:attribute-set name="list.item.spacing">
  <xsl:attribute name="space-before.optimum">0.5em</xsl:attribute>
  <xsl:attribute name="space-before.minimum">0.4em</xsl:attribute>
  <xsl:attribute name="space-before.maximum">0.6em</xsl:attribute>
</xsl:attribute-set>

<!-- Definition list and glossary formatting -->
<xsl:param name="variablelist.as.blocks" select="1"/>
<xsl:param name="glossary.as.blocks" select="1"/>
<xsl:param name="glosslist.as.blocks" select="1"/>

<!-- Ordered list formatting -->
<xsl:param name="orderedlist.label.width">1.8em</xsl:param>

<!-- Override video template to output a video as a link. -->
<xsl:template match="videodata">
  <xsl:variable name="alt">
    <xsl:choose>
      <xsl:when test="ancestor::mediaobject/alt">
        <xsl:apply-templates select="ancestor::mediaobject/alt"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:apply-templates select="(ancestor::mediaobject/textobject/phrase)[1]"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:variable>
  <xsl:variable name="filename">
    <xsl:call-template name="mediaobject.filename">
      <xsl:with-param name="object" select=".."/>
    </xsl:call-template>
  </xsl:variable>
  <fo:basic-link external-destination="{$filename}"><xsl:copy-of select="$alt"/></fo:basic-link>
</xsl:template>

</xsl:stylesheet>
