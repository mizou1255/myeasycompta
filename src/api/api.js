// Simple in-memory cache with TTL (milliseconds)
const _cache = new Map();

export function apiCache(key, ttl, fetcher) {
    const now = Date.now();
    if (_cache.has(key)) {
        const { value, expiresAt } = _cache.get(key);
        if (now < expiresAt) return Promise.resolve(value);
    }
    return fetcher().then(value => {
        _cache.set(key, { value, expiresAt: now + ttl });
        return value;
    });
}

export function clearApiCache(key) {
    if (key) _cache.delete(key);
    else _cache.clear();
}

export function fetchSettings() {
  return apiCache('settings', 60_000, _fetchSettingsRaw);
}

async function _fetchSettingsRaw() {
  try {
    const response = await fetch("/wp-json/my-easy-compta/v1/settings/get", {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        "X-WP-Nonce": myEasyComptaAdmin.nonce,
      },
    });

    if (response.ok) {
      const settings = await response.json();
      let currencySymbol = null;
      let vatData = null;
      let listVatData = null;

      if (settings.default_currency) {
        const currencyId = settings.default_currency;
        const currencyResponse = await fetch(
          `/wp-json/my-easy-compta/v1/settings/currency/${currencyId}`,
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        if (currencyResponse.ok) {
          const currencyData = await currencyResponse.json();
          currencySymbol = currencyData.symbol;
        }
      }

      if (settings.vat_active == 1) {
        const vatId = settings.default_vat;
        const vatResponse = await fetch(
          `/wp-json/my-easy-compta/v1/settings/vat/${vatId}`,
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        if (vatResponse.ok) {
          vatData = await vatResponse.json();
        } else {
        }

        const listVatsResponse = await fetch(
          `/wp-json/my-easy-compta/v1/settings/vats`,
          {
            method: "GET",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": myEasyComptaAdmin.nonce,
            },
          }
        );

        if (listVatsResponse.ok) {
          listVatData = await listVatsResponse.json();
        }
      } else {
        vatData = 0;
        listVatData = 0;
      }

      return {
        settings,
        currencySymbol,
        vatData,
        listVatData,
      };
    } else {
      const error = await response.json();
      throw new Error(error.message);
    }
  } catch (error) {
    throw new Error(error.message);
  }
}
