# MakeEntityCommand Documentation

La commande s'exécute avec bin/console boom:make:entity.

Elle permet de créer une classe PHP relative à une table existante dans SAP (système ou utilisateur).

## Table Utilisateur (UDT)

Choisir une table utilisateur parmi la liste de choix proposée en tapant le nombre, le nom de la table, ou en utilisant les flêches haut et bas pour la sélectionner.

```
What's the name of the table ?:
  [0 ] CorAcctConf
  [1 ] CorCorFs
  [2 ] CorCorLicence
  [3 ] CorCorModules
  [4 ] CorCorModulesuser
  [5 ] CorCorSettings
  [6 ] CorCustomConf
  [7 ] CorCustomField
  [8 ] CorCustomFuncb
  [9 ] CorCustomGscript
  [10] CorCustomNewitems
> 8
```

La classe se crée avec la totalité des champs en propriété, ainsi que les getters et les setters.

## Table Système

Choisir une table utilisateur parmi la liste de choix proposée en tapant le nombre, le nom de la table, ou en utilisant les flêches haut et bas pour la sélectionner.

```
What's the name of the table ?:
  [0  ] ChartOfAccounts
  [1  ] BusinessPartnerGroups
  [2  ] InventoryPostings
  [3  ] UnitOfMeasurementGroups
  [4  ] UnitOfMeasurements
  [5  ] BinLocations
  [6  ] Activities
  ...
> 6
```

Choisir si la génération prend la totalité des champs présent dans la table (non par défaut) :

```
 Want you add all properties to your entity ? (yes/no) [no]:
 > 
```

Si non, choisir un à un les champs souhaités en propriété ce classe. La clé primaire de la table est inclus de base.

```
Properties of BusinessPartners Entity :
---------------------------------------

 * CardCode

 Want you add field in your entity ? (yes/no) [yes]:
 > 

 Wich one ?:
  [0  ] CardName
  [1  ] CardType
  [2  ] GroupCode
  [3  ] Address
  [4  ] ZipCode
  [5  ] MailAddress
  [6  ] MailZipCode
  ...
```

Répondre "no" pour créer l'entité :

```
Properties of BusinessPartners Entity :
---------------------------------------

 * CardCode
 * CardName
 * U_w3c_CCad

 Want you add field in your entity ? (yes/no) [yes]:
 > no

Entity creation...
------------------

 1/1 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

                                                                                                                        
 [OK] Success in entity creation !    
 ```
                                                                                                                        

