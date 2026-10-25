# VIP → Coin geçiş etki raporu

VIP üyeliği (`vip_until` + `access=vip`) kaldırılıyor, yerine coin bakiyesi ve oyun kilidi geliyor. `vip_until` sütunu geri dönüş için duruyor; kod artık okumuyor.

## Veri

| Eski | Yeni |
|---|---|
| `users.vip_until` | `users.coin_balance` (sütun kalır, kullanılmaz) |
| `games.access` (free/vip) | `games.coin_price` (0 = ücretsiz), `sale_price`, `sale_ends_at` |
| `code_batches` + `vip_codes` | `coin_code_batches` + `coin_codes` |
| — | `coin_transactions`, `game_price_history`, `game_unlocks`, `coin_packages`, `coin_package_clicks` |

## Kodda VIP’e dokunan yerler

- Modeller: `User` (`isVip`, `scopeVip`), `Game` (`access`), `VipCode`, `CodeBatch`, `CodeAttempt`
- Enum: `GameAccess`, `VipCodeStatus`
- Servisler: `VipCodeIssuer`, `VipCodeRedeemer`, `VipAdjuster`, `CatalogCache` (vip dilimi)
- Destek: `VipCodeGenerator`, `VipCodeNormalizer`
- Controller: `AccountController` (kod kullan), `GameController::vip`, `DownloadController`, `SignedDownloadController`, `CodeBatchExportController`, `Api\V1\AuthController` (`vip`, `vip_until`)
- İstek: `RedeemVipCodeRequest`, `FilterGamesRequest` (`erisim`)
- Politika: `VipCodePolicy`, `CodeBatchPolicy`, `GameFilePolicy`
- Filament: `UserResource` (`vip_until`), `GameResource` (`access`), `VipCodeResource`, `CodeBatchResource`
- Blade: `vip.blade.php`, `downloads/vip.blade.php`, `account/show`, `download-panel`, `hero`, `games/show`, `home`, `games/index` filtresi
- Rota: `GET /vip`, parti dışa aktarma
- Seeder + `SeedScale`, fabrikalar (`UserFactory::vip`, `GameFactory::vip`)
- Testler: `MembershipTest`, `VipCodeTest`, `DownloadTest`, `CatalogTest`, `CatalogSeederTest`, `EdgeCaseTest`, `GameAuthApiTest`, `SecurityAuditTest`, `ComplianceTest`, e2e `membership.spec.ts`
- Config: `config/membership.php` indirme limitleri
- Migration: `extend_users`, `create_games` (`access`), `create_vip_codes`, `create_code_batches`, `harden_integrity`, `catalog_scale_indexes`

## Bu adımda yapılan

Şema, modeller, fabrikalar, `CoinService` iskeleti (bakiye yalnız buradan değişir), Filament form/kaynakları, seeder. Eski tablolar ve VIP kod yolları duruyor; bir sonraki adım onları sökecek.
