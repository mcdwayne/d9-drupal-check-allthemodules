A field formatter that transports the referenced entities as a pseudo field on the host, mostly for themers pleasure.

Say for example you are having a entity queue attached to your node, but your themer needs the individual queue items in the host node template file, instead of rendering the content.field_queue as a whole.  Instead of having to preprocess it in your .theme file you can conviently set it on the content entity itself, being it a less theme dependend solution.

Practical example:  aka the itch that caused this module to be build in the first place. We have a job listing site where the actual jobs are coming from an ATS vendor. But the we needed some form of content enrichment on the actual vacancy. And that enrichment needed to be flexible and easy to use. That rules out blocks and context. So instead we created entity queue that are coupled to the vacancy node. But the content of that queue needs to be placed intersectional with the vacancy node.

Setting the formatter of that reference field as a reference as field formatter the themer can access the enrichment fields from the queue, intertwine them with the fields of the host node without any problems.

Small code example 
<code>
{# Keyvisual header#}
{{ content.field_content_enrichment.field_vacancy_header }}
{{ title}}
	<p>{{content.body}}</p>
	{{content.field_vacancy_link}}
	{{content.field_content_enrichment.field_vacancy_action}}
</code>	

Enable this module if you need that finegrained control in your templates, but be aware that this module is not aimed at site builder that can't edit the actual twig templates.
