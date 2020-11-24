echo "select * from adijoz order by limits,taken" | psql adijoz
echo "update adijoz set limits='{\"zal\":0,\"wyp\":24,\"dod\":15,\"nz\":0}' where year=2021" | psql adijoz


