# Script for making stub files.
#
# Usage:
#   perl mktopics.pl path/to/template.txt path/for/output < topiclist
#
# This script takes as input (stdin) lines copy/pasted from the tracking
# spreadsheet, which look like this:
#  - Title here<tab>filename.txt
# It parses these lines into the title (without the leading -) and the filename.
# Then it makes a copy of the template with the given file name, and puts
# the title and ID into the template. Note that you will need to separate the
# topics into Task and Concept topics first, and run this script separately on
# each list, because they have different templates.

# Read in the template
$template = $ARGV[0];
open TEMPLATE, $template;
$template_text = join("", <TEMPLATE>);
close TEMPLATE;

# Split it at the ID and the title.
@parts = split /id\-goes\-here/, $template_text;
@more_parts = split /Title Goes Here/, $parts[1];
$before = @parts[0];
$middle = @more_parts[0];
$after = @more_parts[1];

$dir = '>' . $ARGV[1] . '/';

while ($line = <STDIN>) {
    # Lines are:
    #  - Title here<tab>filename.txt
    # Split on whitespace. Last bit is the filename. First bit is the -.
    # Rest is the title, which will need to be pasted back together.

    chomp($line);
    @parts = split /\s/, $line;
    $file = pop @parts;
    shift @parts;
    $title = join ' ', @parts;

    # ID is the filename without the .txt
    @parts = split /\./, $file;
    $id = $parts[0];

    # Make the file.
    $text = $before . $id . $middle . 'UNWRITTEN - ' . $title . $after;

    open OUTFILE, $dir . $file;
    print OUTFILE $text;
    close OUTFILE;

    print "Wrote out $file for $title\n";
}
