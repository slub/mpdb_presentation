/***
 *
 * This file is part of the "Publisher Database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2021 Matthias Richter <matthias.richter@slub-dresden.de>, SLUB Dresden
 *
 ***/

class Timeseries  {

    constructor(data, extent, node, ratio) {
        this.data = data;
        this.extent = extent;
        this.node = node;
        this.ratio = ratio;
    }

    draw() {
        const width = $(node).width;
        const height = this.width * this.ratio;
    }
}
