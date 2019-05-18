var GgaTmCon = Array('r','t','y','p','s','d','f','g','h','j','k','l','z','v','b','n','m','R','T','Y','P','S','D','G','H','J','K','L','Z','V','B','N','M');
var GgaTmUBase = Array('\u0BB0','\u0B9F','\u0BAF','\u0BAA','\u0B9A','\u0BA4','\u0B83','\u0B99','\u0BB9','\u0B9C','\u0B95','\u0BB2','\u0BB4','\u0BB5','\u0BA8','\u0BA9','\u0BAE','\u0BB1','\u0B9F','\u0BAF','\u0BAA','\u0053','\u0BA4','\u0B9E','\u0BB9','\u0B9C','\u0B95','\u0BB2','\u0BB4','\u0BB5','\u0BA8','\u0BA3','\u0BAE');
var GgaTmAd = Array('_','a','q','w','e','u','i','o','Q','W','E','U','I','O','A');
var GgaTmUMod = Array('\u0BCD','_','\u0BC6','\u0BCC','\u0BBF','\u0BC1','\u0BC8','\u0BCA','\u0BC7','\u0BCC','\u0BC0','\u0BC2','\u0BC8','\u0BCB','\u0BBE');

var __GA0640 = new Array();

for(var __c=0;__c < GgaTmCon.length;__c++)
{	for( var __a=0; __a < GgaTmAd.length; __a ++)
	{	switch(__a)
		{ case 0: __GA0640[GgaTmCon[__c]]=GgaTmUBase[__c]+GgaTmUMod[0];break;
		  case 1: __GA0640[GgaTmCon[__c]+GgaTmAd[__a]]=GgaTmUBase[__c];	break;
		  default: __GA0640[GgaTmCon[__c]+GgaTmAd[__a]] = GgaTmUBase[__c]+GgaTmUMod[__a];
		}
	}
}

__GA0640['a']="\u0B85";
__GA0640['A']="\u0B86";
__GA0640['e']="\u0B87";
__GA0640['E']="\u0B88";
__GA0640['u']="\u0B89";
__GA0640['U']="\u0B8A";
__GA0640['q']="\u0B8E";
__GA0640['Q']="\u0B8F";
__GA0640['i']="\u0B90";
__GA0640['o']="\u0B92";
__GA0640['O']="\u0B93";
__GA0640['w']="\u0B94";
__GA0640['+h']="\u0BCD";


__GA0640['`']='\u0060';
__GA0640['1']='\u0BE7';
__GA0640['2']='\u0BE8';
__GA0640['3']='\u0BE9';
__GA0640['4']='\u0BEA';
__GA0640['5']='\u0BEB';
__GA0640['6']='\u0BEC';
__GA0640['7']='\u0BED';
__GA0640['8']='\u0BEE';
__GA0640['9']='\u0BEF';
__GA0640['0']='\u0BE6';

__GA0640['-']='\u002D';
__GA0640['=']='\u003D';
__GA0640['[']='\u005B';
__GA0640[']']='\u005D';
__GA0640[';']='\u003B';
__GA0640['\\']='\u005C';
__GA0640[',']='\u002C';
__GA0640['.']='\u002E';
__GA0640['/']='\u002F';
__GA0640['~']='\u007E';
__GA0640['!']='\u0021';
__GA0640['@']='\u0040';
__GA0640['#']='\u0023';
__GA0640['$']='\u0024';
__GA0640['%']='\u0025';
__GA0640['^']='\u005E';
__GA0640['&']='\u0026';
__GA0640['*']='\u002A';
__GA0640['(']='\u0028';
__GA0640[')']='\u0029';
__GA0640['_']='\u005F';
__GA0640['+']='\u002B';
__GA0640['{']='\u007B';
__GA0640['}']='\u007D';
__GA0640['F']='\u0046';
__GA0640[':']='\u003A';
__GA0640['"']='\u0022';
__GA0640['x']='\u0058';
__GA0640['c']='\u0B9A';
__GA0640['<']='\u003C';
__GA0640['>']='\u003E';
__GA0640['?']='\u003F';
__GA0640['|']='\u007C';

