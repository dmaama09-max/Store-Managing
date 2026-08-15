<?php


class Produit {


  private int $id;
  private string $reference;
  private string $nom;
  private float $prixAchat;
  private float $prixVente;
  private int $quantiteStock;
  private int $seuilAlerte;


  public function __construct (
                                int $id,
                                string $reference,
                                string $nom,
                                float $prixAchat,
                                float $prixVente,
                                int $quantiteStock,
                                int $seuilAlerte    
                            )
                            {
                                $this->id = $id;
                                $this->reference = $reference;
                                $this->nom = $nom;
                                $this->prixAchat = $prixAchat;
                                $this->prixVente = $prixVente;
                                $this->quantiteStock = $quantiteStock;
                                $this->seuilAlerte = $seuilAlerte;
                            }
  
                    public function decrementerStock(int $qte): void{
                                if ($qte > $this->quantiteStock){
                                    throw new \Exception("Stock Insuffisant");
                                }
                                $this->quantiteStock -= $qte;
                    }

                    
//   +incrementerStock(qte: int): void
//   +estEnRupture(): bool
}