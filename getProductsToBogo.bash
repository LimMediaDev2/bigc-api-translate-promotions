#!/bin/bash

storeName=$1
storeHash=$2
accessToken=$3
categories=$4
promotionId=$5

echo "https://api.bigcommerce.com/stores/${storeHash}/v3/promotions/${promotionId}"

curl --silent --location "https://api.bigcommerce.com/stores/${storeHash}/v3/promotions/${promotionId}" \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header "X-Auth-Token: ${accessToken}" \
  --header 'User-Agent: LimMediaCurl1.0' 2>&1 | jq . > output/${storeName}.BOGO.Promotion.json

echo "https://api.bigcommerce.com/stores/${storeHash}/v3/catalog/products?limit=250&page=1&categories%3Ain=${categories}"

echo "ProductID,ProductSKU,ProductName,VariantId,VariantSKU,HasModifiers?" > output/${storeName}.BOGO.Products.csv
curl --silent --location "https://api.bigcommerce.com/stores/${storeHash}/v3/catalog/products?limit=250&page=1&categories%3Ain=${categories}" \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --header "X-Auth-Token: ${accessToken}" \
  --header 'User-Agent: LimMediaCurl1.0' 2>&1 | jq -c ' .data[] | "\(.id),\(.name),\(.sku)"' \
| while read product
do
    productId=$(echo "${product}" | sed -e 's/^"//g;s/"$//g' | cut -d',' -f1)
    productName=$(echo "${product}" | sed -e 's/^"//g;s/"$//g' | cut -d',' -f2 | sed -e 's/"//g')
    productSku=$(echo "${product}" | sed -e 's/^"//g;s/"$//g' | cut -d',' -f3)
    productModifiers=$(curl --silent --location "https://api.bigcommerce.com/stores/${storeHash}/v3/catalog/products/${productId}/modifiers" \
      --header 'Accept: application/json' \
      --header 'Content-Type: application/json' \
      --header "X-Auth-Token: ${accessToken}" \
      --header 'User-Agent: LimMediaCurl1.0' 2>&1 | jq -c ' .meta | .pagination.total')
    curl --silent --location "https://api.bigcommerce.com/stores/${storeHash}/v3/catalog/products/${productId}/variants" \
      --header 'Accept: application/json' \
      --header 'Content-Type: application/json' \
      --header "X-Auth-Token: ${accessToken}" \
      --header 'User-Agent: LimMediaCurl1.0' 2>&1 | jq -c ' .data[] | "\(.id),\(.sku)"' \
    | while read variant
    do
        variantId=$(echo "${variant}" | sed -e 's/^"//g;s/"$//g' | cut -d',' -f1)
        variantSku=$(echo "${variant}" | sed -e 's/^"//g;s/"$//g' | cut -d',' -f2)
        echo "${productId},${productSku},${productName},${variantId},${variantSku},${productModifiers}"
    done
done >> output/${storeName}.BOGO.Products.csv

echo "ProductID,ProductSKU,ProductName,VariantId,VariantSKU,HasModifiers?,InPromotion?" > output/${storeName}.BOGO.Products.Confirmed.csv

cat output/${storeName}.BOGO.Products.csv | egrep -v "^ProductID" | while read line
do
  productId=$(echo "${line}" | cut -d',' -f1)
  variantId=$(echo "${line}" | cut -d',' -f4)
  productExists=$(jq ".data.rules[] | select(.action.gift_item.product_id==${productId}) | .action.gift_item.product_id" output/${storeName}.BOGO.Promotion.json)
  variantExists=$(jq ".data.rules[] | select(.action.gift_item.variant_id==${variantId}) | .action.gift_item.variant_id" output/${storeName}.BOGO.Promotion.json)
  if [ "${variantExists}" = "${variantId}" ]
  then
    echo "${line},TRUE" >> output/${storeName}.BOGO.Products.Confirmed.csv
  else
    if [ ! -z "${productExists}" ] && grep -q "${productId},.*,${variantId}," output/${storeName}.BOGO.Products.csv
    then
      echo "${line},TRUE" >> output/${storeName}.BOGO.Products.Confirmed.csv
    else
      echo "${line},FALSE" >> output/${storeName}.BOGO.Products.Confirmed.csv
    fi
  fi
done

exit 0