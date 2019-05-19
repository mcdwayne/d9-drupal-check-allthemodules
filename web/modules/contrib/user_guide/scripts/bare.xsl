<?xml version="1.0" encoding="ASCII"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:exsl="http://exslt.org/common" xmlns:cf="http://docbook.sourceforge.net/xmlns/chunkfast/1.0" xmlns:ng="http://docbook.org/docbook-ng" xmlns:db="http://docbook.org/ns/docbook" xmlns="http://www.w3.org/1999/xhtml" version="1.0" exclude-result-prefixes="exsl cf ng db">

<!-- This file contains overrides for the user_guide module, for use
     in making XHML output with the xmlto command that is suitable for
     display in the asciidoc_display module. See script mkoutput.sh for
     usage. -->

<!-- No title on the abstract for title page. -->
<xsl:param name="abstract.notitle.enabled" select="1"/>

<!-- Use outline numbering for sections. -->
<xsl:param name="section.autolabel" select="1"/>
<xsl:param name="section.autolabel.max.depth">1</xsl:param>
<xsl:param name="section.label.includes.component.label" select="1"/>
<xsl:param name="preface.autolabel">i</xsl:param>

<!-- Use item IDs as file names, for portability as content shifts. -->
<xsl:variable name="use.id.as.filename">
  <xsl:value-of select="'1'" />
</xsl:variable>

<!-- Set the maximum depth for tables of contents to 2. -->
<xsl:variable name="toc.max.depth">
  <xsl:value-of select="'2'" />
</xsl:variable>

<!-- Use UL lists for tables of contents, not DL lists. -->
<xsl:variable name="toc.list.type">
  <xsl:value-of select="'ul'" />
</xsl:variable>

<!-- Omit a troublesome span in the TOC line. -->
<xsl:template name="toc.line">
  <xsl:param name="toc-context" select="."/>
  <xsl:param name="depth" select="1"/>
  <xsl:param name="depth.from.context" select="8"/>

  <!-- * if $autotoc.label.in.hyperlink is zero, then output the label -->
  <!-- * before the hyperlinked title (as the DSSSL stylesheet does) -->
  <xsl:if test="$autotoc.label.in.hyperlink = 0">
    <xsl:variable name="label">
      <xsl:apply-templates select="." mode="label.markup"/>
    </xsl:variable>
    <xsl:copy-of select="$label"/>
    <xsl:if test="$label != ''">
      <xsl:value-of select="$autotoc.label.separator"/>
    </xsl:if>
  </xsl:if>

  <a>
    <xsl:attribute name="href">
      <xsl:call-template name="href.target">
        <xsl:with-param name="context" select="$toc-context"/>
        <xsl:with-param name="toc-context" select="$toc-context"/>
      </xsl:call-template>
    </xsl:attribute>

  <!-- * if $autotoc.label.in.hyperlink is non-zero, then output the label -->
  <!-- * as part of the hyperlinked title -->
  <xsl:if test="not($autotoc.label.in.hyperlink = 0)">
    <xsl:variable name="label">
      <xsl:apply-templates select="." mode="label.markup"/>
    </xsl:variable>
    <xsl:copy-of select="$label"/>
    <xsl:if test="$label != ''">
      <xsl:value-of select="$autotoc.label.separator"/>
    </xsl:if>
  </xsl:if>

    <xsl:apply-templates select="." mode="titleabbrev.markup"/>
  </a>
</xsl:template>

<!-- Override the output, to omit HTML headers and put titles in divs. -->
<xsl:template name="chunk-element-content">
  <xsl:param name="prev"/>
  <xsl:param name="next"/>
  <xsl:param name="nav.context"/>
  <xsl:param name="content">
    <xsl:apply-imports/>
  </xsl:param>
  <xsl:variable name="home" select="/*[1]"/>
  <xsl:variable name="this" select="."/>
  <div id="asciidoc-display-book-title">
    <xsl:apply-templates select="$home" mode="object.title.markup.textonly"/>
  </div>
  <div id="asciidoc-display-section-title">
    <xsl:apply-templates select="$this" mode="object.title.markup.textonly"/>
  </div>
  <div class="asciidoc-display-main-content">
    <xsl:copy-of select="$content"/>
    <xsl:call-template name="footer.navigation">
      <xsl:with-param name="prev" select="$prev"/>
      <xsl:with-param name="next" select="$next"/>
      <xsl:with-param name="nav.context" select="$nav.context"/>
    </xsl:call-template>
  </div>
</xsl:template>

<!-- Override the output class for programlistings to give an extra class
     if it is PHP -->
<xsl:template match="programlisting" mode="common.html.attributes">
  <xsl:variable name="programtype" select="./@language" />
  <xsl:choose>
    <xsl:when test="$programtype = 'php'">
      <xsl:attribute name="class">programlisting programlisting-php</xsl:attribute>
    </xsl:when>
    <xsl:otherwise>
      <xsl:attribute name="class">programlisting</xsl:attribute>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<!-- Override the output for section headers to add the ID in plain text -->
<xsl:template name="section.heading">
  <xsl:param name="section" select="."/>
  <xsl:param name="level" select="1"/>
  <xsl:param name="allow-anchors" select="1"/>
  <xsl:param name="title"/>
  <xsl:param name="class" select="'title'"/>

  <xsl:variable name="id">
    <xsl:choose>
      <!-- Make sure the subtitle doesn't get the same id as the title -->
      <xsl:when test="self::subtitle">
        <xsl:call-template name="object.id">
          <xsl:with-param name="object" select="."/>
        </xsl:call-template>
      </xsl:when>
      <!-- if title is in an *info wrapper, get the grandparent -->
      <xsl:when test="contains(local-name(..), 'info')">
        <xsl:call-template name="object.id">
          <xsl:with-param name="object" select="../.."/>
        </xsl:call-template>
      </xsl:when>
      <xsl:otherwise>
        <xsl:call-template name="object.id">
          <xsl:with-param name="object" select=".."/>
        </xsl:call-template>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:variable>

  <!-- HTML H level is one higher than section level -->
  <xsl:variable name="hlevel">
    <xsl:choose>
      <!-- highest valid HTML H level is H6; so anything nested deeper
           than 5 levels down just becomes H6 -->
      <xsl:when test="$level &gt; 5">6</xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$level + 1"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:variable>
  <xsl:element name="h{$hlevel}" namespace="http://www.w3.org/1999/xhtml">
    <xsl:attribute name="class"><xsl:value-of select="$class"/></xsl:attribute>
    <xsl:if test="$css.decoration != '0'">
      <xsl:if test="$hlevel&lt;3">
        <xsl:attribute name="style">clear: both</xsl:attribute>
      </xsl:if>
    </xsl:if>
    <xsl:if test="$allow-anchors != 0">
      <xsl:call-template name="anchor">
        <xsl:with-param name="node" select="$section"/>
        <xsl:with-param name="conditional" select="0"/>
      </xsl:call-template>
    </xsl:if>
    <xsl:copy-of select="$title"/>
  </xsl:element>
  <xsl:if test="$level&lt;2">
    <xsl:call-template name="add_id">
      <xsl:with-param name="node" select="$section"/>
    </xsl:call-template>
  </xsl:if>
</xsl:template>

<!-- Template that actually adds the IDs for section titles -->
<xsl:template name="add_id">
  <xsl:param name="node" select="."/>
  <xsl:if test="$node/@id or $node/@xml:id">
    <p><em><span>
      <xsl:attribute name="class">remark</xsl:attribute>ID:
      <xsl:call-template name="object.id">
        <xsl:with-param name="object" select="$node"/>
      </xsl:call-template>
    </span></em></p>
  </xsl:if>
</xsl:template>

<!-- Title page with cover image, omitting book title since that appears
     already in the output. -->
<xsl:template name="book.titlepage.recto">
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
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/othercredit"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="info/othercredit"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/releaseinfo"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="info/releaseinfo"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/copyright"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="info/copyright"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/legalnotice"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="info/legalnotice"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/pubdate"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="info/pubdate"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/revision"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="info/revision"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/revhistory"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="info/revhistory"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="bookinfo/abstract"/>
  <xsl:apply-templates mode="book.titlepage.recto.auto.mode" select="info/abstract"/>
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

<!-- Override video template to output in an iframe instead of an embed tag.
     Customized for our purpose; doesn't support attributes in the original
     except the URL of the video and the title. -->
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
  <xsl:element name="iframe" namespace="http://www.w3.org/1999/xhtml">
    <xsl:attribute name="id">ytplayer</xsl:attribute>
    <xsl:attribute name="type">text/html</xsl:attribute>
    <xsl:attribute name="width">640</xsl:attribute>
    <xsl:attribute name="height">360</xsl:attribute>
    <xsl:attribute name="src">
      <xsl:value-of select="concat($filename, '?modestbranding=1&amp;rel=0&amp;autoplay=0')"/>
    </xsl:attribute>
    <xsl:attribute name="alt">
      <xsl:value-of select="$alt"/>
    </xsl:attribute>
    <xsl:attribute name="frameborder">0</xsl:attribute>
    <xsl:attribute name="allowfullscreen">allowfullscreen</xsl:attribute>
  </xsl:element>
</xsl:template>

</xsl:stylesheet>
