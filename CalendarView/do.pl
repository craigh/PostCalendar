#!/usr/bin/perl

$namespace = 'Zikula\PostCalendar\CalandarView\Nav';

foreach $infile (@ARGV)  {

    $outfile = $infile . ".tmp";
    
    open(IN,$infile);
    open(OUT,">$outfile");
    
    $fc = 0;
    while(<IN>) {

        if ( $fc ) {
            s/^class PostCalendar_(\w+)_(\w+)/class $1$2/;
        }

        print OUT $_;

        unless($fc) {
            if ( m!\*/! ) {
                print OUT "\nnamespace $namespace;\n";
                $fc = 1;
            }
        }
        
    }
    close IN;
    close OUT;
    
    rename($outfile, $infile);

}
