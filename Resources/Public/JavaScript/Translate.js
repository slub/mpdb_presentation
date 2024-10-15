function tx_mpdbpresentation_translate (input) {
    let multiPartMap = 
        {
            'A': 'Alt',
            'B' : 'Bass',
            'Ba': 'Bariton',
            'BC': 'Basso Continuo',
            'C': 'Cembalo',
            'Div': 'Diverse',
            'Fg': 'Fagott',
            'Fl': 'Flöte',
            'Gi': 'Gitarre',
            'Gs': 'Gesang',
            'Hr': 'Horn',
            'Kl': 'Klarinette',
            'K': 'Klavier',
            'K4h': 'Klavier vierhändig',
            'Kb': 'Kontrabass',
            'Ma': 'Mandola',
            'Ml': 'Mandoline',
            'Ms': 'Mezzosopran',
            'Ob': 'Oboe',
            'Or': 'Orgel',
            'Pa': 'Pauke',
            'Po': 'Posaune',
            'S': 'Sopran',
            'Tm': 'Tamborin',
            'T': 'Tenor',
            'Ti': 'Timpani',
            'Tn': 'Triangel',
            'TnTm': 'Triangel und Tamburin',
            'Tr': 'Trompete',
            'Tu': 'Tuba',
            'Va': 'Viola',
            'Vl': 'Violine',
            'Vc': 'Violoncello',
            'Zi': 'Zither',
            'Blä': 'Bläsersatz/-stimmen',
            'Qu': 'Quartettstimmen',
            'Str': 'Streichersatz',
            'Prc': 'Schlag-/Percussionstimmen'
        };

    const onePartMap = 
        {
            'N': '',
            'Cplt': 'Complett',
            'PStC': 'Partitur und Stimmen',
            '2K4h': '2 Klaviere (4hd.)',
            '2K8h': '2 Klaviere (8hd.)',
            'K4h': 'Klavier (4hd.)',
            'KA4h': 'Klavierauszug vierhändig',
            'KAmT': 'Klavierauszug mit Text',
            'KAoT': 'Klavierauszug ohne Text',
            'KA': 'Klavierauszug',
            'StC': 'Stimmen Complett',
            'KAB': 'Klavierauszug Begleitung',
            'K': 'Klavier (2hd.)',
            'P': 'Partitur',
            'PCh': 'Chorstimmenpartitur',
            'POr': 'Orchesterstimmenpartitur',
            'StCOr': 'Kompletter Orchesterstimmensatz',
            'StCCh': 'Kompletter Chorstimmensatz',
            'StCChSoloGs': 'Kompletter Chorstimmensatz, Solostimme Gesang',
            'StCChSATB': 'Kompletter Chorstimmensatz SATB',
            'StCChTTBB': 'Kompletter Chorstimmensatz TTBB',
            'StCSolo': 'Kompletter Solostimmensatz',
            'StInc': 'Inkompletter Stimmensatz',
            'hS': 'Hohe Stimme',
            'mS': 'Mittlere Stimme',
            'tS': 'Tiefe Stimme',
            'tA': 'Tiefer Alt',
            'Nbsp': 'Notenbeispiele',
            'Kad': 'Kadenzen'
        };

    const voiceMap = {
        'N': 1,
        'Cplt': 2,
        'P': 3,
        'K': 11,
        'K4h': 12,
        '2K4h': 13,
        '2K8h': 14,
        'C': 15,
        'BC': 16,
        'Or': 17,
        'KA': 21,
        'KA4h': 22,
        'KAmT': 23,
        'KAoT': 24,
        'StC': 31,
        'PCh': 101,
        'Or': 102,
        'POr': 103,
        'KAB': 111,
        'StCOr': 121,
        'StCCh': 122,
        'StCChSATB': 123,
        'StCChTTBB': 124,
        'StCSolo': 125,
        'StInc': 126,
        'StInst': 127,
        'StOber': 128,
        'St': 129,
        'hS': 131,
        'mS': 132,
        'tS': 133,
        'tA': 134,
        'TrA': 141,
        'Nbsp': 151,
        'Kad': 152,
        'S1': 201,
        'S2': 202,
        'S3': 203,
        'EStS': 204,
        'EStS1': 205,
        'EStS2': 206,
        'EStS3': 207,
        'Ms1': 211,
        'Ms2': 212,
        'Ms3': 213,
        'EStMs': 214,
        'EStMs1': 215,
        'EStMs2': 216,
        'EStMs3': 217,
        'A1': 221,
        'A2': 222,
        'A3': 223,
        'EStA': 224,
        'EStA1': 225,
        'EStA2': 226,
        'EStA3': 227,
        'T1': 231,
        'T2': 232,
        'T3': 233,
        'EStT': 234,
        'EStT1': 235,
        'EStT2': 236,
        'EStT3': 237,
        'Ba1': 241,
        'Ba2': 242,
        'Ba3': 243,
        'EStBa': 244,
        'EStBa1': 245,
        'EStBa2': 246,
        'EStBa3': 247,
        'B1': 251,
        'B2': 252,
        'B3': 253,
        'EStB': 254,
        'EStB1': 255,
        'EStB2': 256,
        'EStB3': 257,
        'Vl': 401,
        'EStVl': 402,
        'EStVl1': 403,
        'EStVl2': 404,
        'Va': 412,
        'EStVa': 413,
        'Vc': 414,
        'EStVc': 415,
        'Kb': 416,
        'EStKb': 417,
        'Pi': 500,
        'EStPi': 501,
        'Fl': 502,
        'EStFl': 503,
        'Kl': 504,
        'EStKl': 505,
        'Ob': 506,
        'EStOb': 507,
        'Fg': 508,
        'EStFg': 509,
        'Tr': 601,
        'EStTr': 601,
        'Hr': 603,
        'EStHr': 604,
        'Po': 604,
        'EStPo': 606,
        'Tu': 609,
        'EStTu': 610,
        'Gi': 701,
        'Gs': 702,
        'Ha': 703,
        'Ma': 704,
        'Ml': 705,
        'Zi': 901,
        'Pa': 911,
        'Tm': 912,
        'Ti': 913,
        'Tn': 914,
        'Div': 990,
        'Blä': 1001,
        'EStBlä': 1002,
        'Qu': 1010,
        'EStQu': 1011,
        'Str': 1020,
        'EStStr': 1021,
        'Qui': 1003,
        'Str': 1004,
        'Prc': 1005 }

    const publisherMap = 
        {
            'PE': 'C. F. Peters',
            'HO': 'Hofmeister',
            'RB': 'Rieter-Biedermann'
        };

    const keys = input.split('_');
    let part = '';
    if (keys[3] == 'NN') {
        part = '';
    } else if (keys[3].includes('N')) {
        part = 'Nummer ' + keys[3].match(/\d+/)[0];
    } else if (keys[3].includes('H')) {
        part = 'Heft ' + keys[3].match(/\d+/)[0];
    } else if (keys[3].includes('Bd')) {
        part = 'Band ' + keys[3].match(/\d+/)[0];
    } else if (keys[3] == 'Ouv') {
        part = 'Ouvertüre' + ', ';
    } else if (keys[3] == 'Text') {
        part = 'Textband' + ', ';
    }

    let voice = '';
    if (onePartMap[keys[4]] != undefined) {
        voice = onePartMap[keys[4]];
    } else {
        const vs = keys[4].match(
            /(ESt|Solo|)[A-Z][C\da-z]?[T\da-z]?[a-z]?/g);
        for (let v of vs) {
            if (v.includes('ESt')) {
                voice += 'Einzelstimme ';
                v = v.replace('ESt', '');
            } else if (v.includes('Solo')) {
                voice += 'Solostimme ';
                v = v.replace('Solo', '');
            }
            let number = '';
            if (v.match(/\d/)) {
                number = v.match(/\d/);
                v = v.replace(/\d/, '');
            }
            voice += multiPartMap[v] + ' ' + number;
        }
    }

    const plateId = keys[2].match(/[1-9]\d*[a-f]?/);

    const publisher = publisherMap[keys[0]];

    let partVoice = part;
    let parentheses = [ '(', ')' ];
    if (part == '' && voice == '') {
        parentheses = [ '', '' ];
    }
    if (part != '' && voice != '') {
        partVoice += ', ';
    }
    partVoice += voice;

    return partVoice + ' ' + parentheses[0] + publisher + ', Plattennr. ' + plateId + parentheses[1];

}
