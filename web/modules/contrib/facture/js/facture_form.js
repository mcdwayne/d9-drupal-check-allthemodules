(function ($) {
    Drupal.behaviors.facture = {
        attach: function (context, settings) {

            function recalculateTotalCotisation() {

                //total remises
                var rmMult = parseFloat($('#edit-field-remises-und-0-field-remise-multi-und-0-value').val().replace(',','.'));
                rmMult = isNaN(rmMult) ? 0 : rmMult;
                var rmExfm = parseFloat($('#edit-field-remises-und-0-field-remise-ex-fm-und-0-value').val().replace(',','.'));
                rmExfm = isNaN(rmExfm) ? 0 : rmExfm;
                var rmParr = parseFloat($('#edit-field-remises-und-0-field-remise-parrainage-und-0-value').val().replace(',','.'));
                rmParr = isNaN(rmParr) ? 0 : rmParr;

                var remiseCalcTotale = rmMult + rmExfm +rmParr;
                var remiseCalcTotaleFR = remiseCalcTotale.toFixed(2).replace('.',',');
                $('#edit-field-remise-totale-und-0-value').val(remiseCalcTotaleFR);


                //total cotisation
                var brut = parseFloat($('#edit-field-total-brut-adhesion-und-0-value').val().replace(',','.'));
                brut = isNaN(brut) ? 0 : brut;
                var remiseTotale = parseFloat($('#edit-field-remise-totale-und-0-value').val().replace(',','.'));
                remiseTotale = isNaN(remiseTotale) ? 0 : remiseTotale;

                var cotisation = brut - remiseTotale;
                var cotisationFR = cotisation.toFixed(2).replace('.',',');
                $('#edit-field-total-net-adhesion-und-0-value').val(cotisationFR);


                //total règlements

                var rgAcompte = parseFloat($('#edit-field-acompte-und-0-field-montant-chk-acompte-und-0-value').val().replace(',','.'));
                rgAcompte = isNaN(rgAcompte) ? 0 : rgAcompte;
                var rgDebit1 = parseFloat($('#edit-field-debit-1-und-0-field-montant-chk-debit1-und-0-value').val().replace(',','.'));
                rgDebit1 = isNaN(rgDebit1) ? 0 : rgDebit1;
                var rgDebit2 = parseFloat($('#edit-field-debit-2-und-0-field-montant-chk-debit2-und-0-value').val().replace(',','.'));
                rgDebit2 = isNaN(rgDebit2) ? 0 : rgDebit2;
                var rgDebit3 = parseFloat($('#edit-field-debit-3-und-0-field-montant-chk-debit3-und-0-value').val().replace(',','.'));
                rgDebit3 = isNaN(rgDebit3) ? 0 : rgDebit3;
                var rgDebit4 = parseFloat($('#edit-field-debit-4-und-0-field-montant-chk-debit4-und-0-value').val().replace(',','.'));
                rgDebit4 = isNaN(rgDebit4) ? 0 : rgDebit4;
                var rgDebit5 = parseFloat($('#edit-field-debit-5-und-0-field-montant-chk-debit5-und-0-value').val().replace(',','.'));
                rgDebit5 = isNaN(rgDebit5) ? 0 : rgDebit5;
                var rgDebit6 = parseFloat($('#edit-field-debit-6-und-0-field-montant-chk-debit6-und-0-value').val().replace(',','.'));
                rgDebit6 = isNaN(rgDebit6) ? 0 : rgDebit6;
                var rgDebit7 = parseFloat($('#edit-field-debit-7-und-0-field-montant-chk-debit7-und-0-value').val().replace(',','.'));
                rgDebit7 = isNaN(rgDebit7) ? 0 : rgDebit7;
                var rgDebit8 = parseFloat($('#edit-field-debit-8-und-0-field-montant-chk-debit8-und-0-value').val().replace(',','.'));
                rgDebit8 = isNaN(rgDebit8) ? 0 : rgDebit8;
                var rgDebit9 = parseFloat($('#edit-field-debit-9-und-0-field-montant-chk-debit9-und-0-value').val().replace(',','.'));
                rgDebit9 = isNaN(rgDebit9) ? 0 : rgDebit9;
                var rgDebit10 = parseFloat($('#edit-field-debit-10-und-0-field-montant-chk-debit10-und-0-value').val().replace(',','.'));
                rgDebit10 = isNaN(rgDebit10) ? 0 : rgDebit10;
                var rgDebit11 = parseFloat($('#edit-field-debit-11-und-0-field-montant-chk-debit11-und-0-value').val().replace(',','.'));
                rgDebit11 = isNaN(rgDebit11) ? 0 : rgDebit11;
                var rgDebit12 = parseFloat($('#edit-field-debit-12-und-0-field-montant-chk-debit12-und-0-value').val().replace(',','.'));
                rgDebit12 = isNaN(rgDebit12) ? 0 : rgDebit12;

                var rgLiqu1 = parseFloat($('#edit-field-liquide-1-und-0-field-montant-liquide-1-und-0-value').val().replace(',','.'));
                rgLiqu1 = isNaN(rgLiqu1) ? 0 : rgLiqu1;
                var rgLiqu2 = parseFloat($('#edit-field-liquide-2-und-0-field-montant-liquide-2-und-0-value').val().replace(',','.'));
                rgLiqu2 = isNaN(rgLiqu2) ? 0 : rgLiqu2;

                var rgTotal = rgAcompte+rgDebit1+rgDebit2+rgDebit3+rgDebit4+rgDebit5+rgDebit6+rgDebit7+rgDebit8+rgDebit9+rgDebit10+rgDebit11+rgDebit12+rgLiqu1+rgLiqu2;
                var rgTotalFR = rgTotal.toFixed(2).replace('.',',');
                $('#edit-field-reglement-total-und-0-value').val(rgTotalFR);


                //Ecart

                var ecart = rgTotal-cotisation;
                var ecartFR = ecart.toFixed(2).replace('.',',');
                $('#edit-field-ecart-und-0-value').val(ecartFR);

                //Remboursement
                var rb1 = parseFloat($('#edit-field-remboursement-1-und-0-field-rb-montant-chk1-und-0-value').val().replace(',','.'));
                rb1 = isNaN(rb1) ? 0 : rb1;
                var rb2 = parseFloat($('#edit-field-remboursement-2-und-0-field-rb-montant-chk2-und-0-value').val().replace(',','.'));
                rb2 = isNaN(rb2) ? 0 : rb2;

                var rbTotal = rb1+rb2;
                var rbTotalFR = rbTotal.toFixed(2).replace('.',',');
                $('#edit-field-remboursement-total-und-0-value').val(rbTotalFR);
            }
            // run recalculateTotalCotisation every time user enters a new value
            var fieldBrut = $('#edit-field-total-brut-adhesion-und-0-value');
            fieldBrut.change( recalculateTotalCotisation );
            var fieldRemise = $('#edit-field-remise-totale-und-0-value');
            fieldRemise.change( recalculateTotalCotisation );
            var fieldNet = $('#edit-field-total-net-adhesion-und-0-value');
            fieldNet.change( recalculateTotalCotisation );
            var fieldRmMult = $('#edit-field-remises-und-0-field-remise-multi-und-0-value');
            fieldRmMult.change( recalculateTotalCotisation );
            var fieldRmExfm = $('#edit-field-remises-und-0-field-remise-ex-fm-und-0-value');
            fieldRmExfm.change( recalculateTotalCotisation );
            var fieldRmParr = $('#edit-field-remises-und-0-field-remise-parrainage-und-0-value');
            fieldRmParr.change( recalculateTotalCotisation );

            var fieldrgAcompte = $('#edit-field-acompte-und-0-field-montant-chk-acompte-und-0-value');
            fieldrgAcompte.change( recalculateTotalCotisation );
            var fieldrgDebit1 = $('#edit-field-debit-1-und-0-field-montant-chk-debit1-und-0-value');
            fieldrgDebit1.change( recalculateTotalCotisation );
            var fieldrgDebit2 = $('#edit-field-debit-2-und-0-field-montant-chk-debit2-und-0-value');
            fieldrgDebit2.change( recalculateTotalCotisation );
            var fieldrgDebit3 = $('#edit-field-debit-3-und-0-field-montant-chk-debit3-und-0-value');
            fieldrgDebit3.change( recalculateTotalCotisation );
            var fieldrgDebit4 = $('#edit-field-debit-4-und-0-field-montant-chk-debit4-und-0-value');
            fieldrgDebit4.change( recalculateTotalCotisation );
            var fieldrgDebit5 = $('#edit-field-debit-5-und-0-field-montant-chk-debit5-und-0-value');
            fieldrgDebit5.change( recalculateTotalCotisation );
            var fieldrgDebit6 = $('#edit-field-debit-6-und-0-field-montant-chk-debit6-und-0-value');
            fieldrgDebit6.change( recalculateTotalCotisation );
            var fieldrgDebit7 = $('#edit-field-debit-7-und-0-field-montant-chk-debit7-und-0-value');
            fieldrgDebit7.change( recalculateTotalCotisation );
            var fieldrgDebit8 = $('#edit-field-debit-8-und-0-field-montant-chk-debit8-und-0-value');
            fieldrgDebit8.change( recalculateTotalCotisation );
            var fieldrgDebit9 = $('#edit-field-debit-9-und-0-field-montant-chk-debit9-und-0-value');
            fieldrgDebit9.change( recalculateTotalCotisation );
            var fieldrgDebit10 = $('#edit-field-debit-10-und-0-field-montant-chk-debit10-und-0-value');
            fieldrgDebit10.change( recalculateTotalCotisation );
            var fieldrgDebit11 = $('#edit-field-debit-11-und-0-field-montant-chk-debit11-und-0-value');
            fieldrgDebit11.change( recalculateTotalCotisation );
            var fieldrgDebit12 = $('#edit-field-debit-12-und-0-field-montant-chk-debit12-und-0-value');
            fieldrgDebit12.change( recalculateTotalCotisation );

            var fieldLiqu1 = $('#edit-field-liquide-1-und-0-field-montant-liquide-1-und-0-value');
            fieldLiqu1.change( recalculateTotalCotisation );
            var fieldLiqu2 = $('#edit-field-liquide-2-und-0-field-montant-liquide-2-und-0-value');
            fieldLiqu2.change( recalculateTotalCotisation );

            var fieldrgTotal = $('#edit-field-reglement-total-und-0-value');
            fieldrgTotal.change( recalculateTotalCotisation );

            var fieldEcart = $('#edit-field-ecart-und-0-value');
            fieldEcart.change( recalculateTotalCotisation );

            var fieldrb1 = $('#edit-field-remboursement-1-und-0-field-rb-montant-chk1-und-0-value');
            fieldrb1.change( recalculateTotalCotisation );
            var fieldrb2 = $('#edit-field-remboursement-2-und-0-field-rb-montant-chk2-und-0-value');
            fieldrb2.change( recalculateTotalCotisation );
            var fieldrbTotal = $('#edit-field-remboursement-total-und-0-value');
            fieldrbTotal.change( recalculateTotalCotisation );

// ****************************************************************
        }
    }
})(jQuery, Drupal, drupalSettings);
