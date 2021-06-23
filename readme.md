#Resourcery

Resource-Role-User Authorization Control Package

### 기능

- DB 기반의 유저-역할-리소스 관계 설정
- DB 기반의 동적인 Route 생성
- DB 기반의 동적인 Gate 생성
  - Gate 로 확장되는 다른 코드들과 호환됨
- Route 및 Gate 선언 -> 정적 라우트 파일로 추출
- Resource Model, Controller, DB에 리소스 기본 정보 추가 스케폴딩 커멘드 지원
    - `php artisan resourcery:make ResourceName`
    - 사전에 리소스 DB가 마이그레이션 되어 있어야 함.