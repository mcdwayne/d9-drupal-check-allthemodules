<?php

namespace Drupal\wallet;


class WalletApi
{
    public function getWalletInfoPerUser($uid){

        $balance = array();
        $query = \Drupal::database()->select('wallet_category', 'wc');
        $query->fields('wc', ['id', 'name']);
        $data = $query->execute();
        $categories = $data->fetchAll();
        foreach ($categories as $key => $value)
        {
            $query = \Drupal::database()->select('wallet_transaction', 'wt');
            $query->fields('wt', ['amount']);
            $query->condition('category',$value->id);
            $query->condition('status','Approved');
            $query->condition('user_id',$uid);
            $data = $query->execute();
            $transactions = $data->fetchAll();
            if (!empty($transactions)){
                $sum = 0;
                foreach ($transactions as $key1 => $value1)
                {
                    $sum += $value1->amount;
                }
                $balance[$value->name] = $sum;
            }
            else{
                $balance[$value->name] = 0;
            }
        }

        return $balance;
    }
}